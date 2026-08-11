<?php

namespace App\Model;

class TarefaModel extends BaseModel
{
    const STATUS_A_FAZER      = 'a_fazer';
    const STATUS_EM_ANDAMENTO = 'em_andamento';
    const STATUS_CONCLUIDO    = 'concluido';

    // Campos editaveis via /Tarefa/atualizar. status fica de fora de proposito
    // -- mudanca de status so pode acontecer via moverStatus() (RN04), que
    // valida a transicao. Deixar status aqui abriria brecha pra pular etapas
    // via POST direto (mesma licao do mass-assignment do PlanoCompra).
    private const CAMPOS_EDITAVEIS = [
        'titulo', 'descricao', 'responsavel_id', 'data_limite',
    ];

    // RN04: mapa de transições válidas do Kanban. A chave é o status atual,
    // o valor é a lista de status para onde ele pode ir a partir dali.
    // Permite tanto avançar quanto voltar uma casa (ex: reabrir uma tarefa
    // marcada como concluída por engano), mas nunca pular etapas.
    private const TRANSICOES_VALIDAS = [
        self::STATUS_A_FAZER      => [self::STATUS_EM_ANDAMENTO],
        self::STATUS_EM_ANDAMENTO => [self::STATUS_A_FAZER, self::STATUS_CONCLUIDO],
        self::STATUS_CONCLUIDO    => [self::STATUS_EM_ANDAMENTO],
    ];

    protected $validationRules = [
        'titulo' => ['rules' => 'required|min:3|max:150', 'label' => 'Título'],
    ];

    /**
     * criar
     *
     * @param array $dados ('projeto_id', 'titulo', 'descricao'?, 'responsavel_id'?, 'data_limite'?)
     * @return int
     */
    public function criar(array $dados): int
    {
        $sql = "INSERT INTO tarefas (projeto_id, responsavel_id, titulo, descricao, status, data_limite)
                VALUES (:projeto_id, :responsavel_id, :titulo, :descricao, :status, :data_limite)";

        return $this->connDb->insert($sql, [
            'projeto_id'     => $dados['projeto_id'],
            'responsavel_id' => $dados['responsavel_id'] ?? null,
            'titulo'         => $dados['titulo'],
            'descricao'      => $dados['descricao'] ?? null,
            'status'         => self::STATUS_A_FAZER,
            'data_limite'    => $dados['data_limite'] ?? null,
        ]);
    }

    /**
     * buscarPorId
     *
     * @param int $id
     * @return array
     */
    public function buscarPorId(int $id): array
    {
        $sql = "SELECT * FROM tarefas WHERE id = :id LIMIT 1";
        return $this->connDb->select($sql, ['id' => $id], 'one');
    }

    /**
     * listarPorProjeto
     * Já devolve, por linha, a flag calculada 'atrasada' (RN06) -- não fica
     * armazenada no banco porque isso poderia ficar desatualizado; é
     * recalculada toda vez que a lista é montada.
     *
     * @param int $projetoId
     * @return array
     */
    public function listarPorProjeto(int $projetoId): array
    {
        $sql = "SELECT t.*, u.nome AS responsavel_nome
                FROM tarefas t
                LEFT JOIN usuarios u ON u.id = t.responsavel_id
                WHERE t.projeto_id = :projeto_id
                ORDER BY t.data_limite IS NULL, t.data_limite ASC";

        $tarefas = $this->connDb->select($sql, ['projeto_id' => $projetoId]);

        foreach ($tarefas as &$tarefa) {
            $tarefa['atrasada']     = $this->calcularAtraso($tarefa);
            $tarefa['prazo_valido'] = $this->temPrazoValido($tarefa);
        }

        return $tarefas;
    }

    /**
     * calcularAtraso
     * RN06: uma tarefa está atrasada se a data_limite já passou e ela ainda
     * não foi concluída. Puramente calculado, sem coluna própria no banco.
     *
     * @param array $tarefa
     * @return bool
     */
    public function calcularAtraso(array $tarefa): bool
    {
        if (!$this->temPrazoValido($tarefa) || $tarefa['status'] === self::STATUS_CONCLUIDO) {
            return false;
        }

        $dataLimite = \DateTime::createFromFormat('Y-m-d', $tarefa['data_limite']);

        return $dataLimite < new \DateTime('today');
    }

    /**
     * temPrazoValido
     * Considera "sem prazo" tanto NULL/'' quanto o zero-date '0000-00-00'
     * (linha antiga que ficou gravada por causa do bug do TarefaModel::criar()
     * -- ver correcao em Tarefa::criar()). Centraliza essa checagem pra nao
     * espalhar o mesmo if em varios lugares (Model e View).
     *
     * @param array $tarefa
     * @return bool
     */
    public function temPrazoValido(array $tarefa): bool
    {
        if (empty($tarefa['data_limite']) || $tarefa['data_limite'] === '0000-00-00') {
            return false;
        }

        $dataLimite = \DateTime::createFromFormat('Y-m-d', $tarefa['data_limite']);

        return $dataLimite instanceof \DateTime && $dataLimite->format('Y-m-d') === $tarefa['data_limite'];
    }

    /**
     * moverStatus
     * RN04: só deixa mover para um status que conste no mapa de transições
     * válidas a partir do status atual. Também grava concluida_em quando o
     * destino é 'concluido' (e limpa se a tarefa for reaberta).
     *
     * @param int $tarefaId
     * @param string $novoStatus
     * @return bool true se moveu, false se a transição não é permitida
     */
    public function moverStatus(int $tarefaId, string $novoStatus): bool
    {
        $tarefa = $this->buscarPorId($tarefaId);

        if (count($tarefa) === 0) {
            return false;
        }

        $statusAtual = $tarefa['status'];

        $transicoesPermitidas = self::TRANSICOES_VALIDAS[$statusAtual] ?? [];

        if (!in_array($novoStatus, $transicoesPermitidas, true)) {
            return false; // RN04: transição inválida, bloqueia
        }

        $concluidaEm = $novoStatus === self::STATUS_CONCLUIDO ? date('Y-m-d H:i:s') : null;

        $sql = "UPDATE tarefas
                SET status = :status, concluida_em = :concluida_em
                WHERE id = :id";

        $this->connDb->update($sql, [
            'status'       => $novoStatus,
            'concluida_em' => $concluidaEm,
            'id'           => $tarefaId,
        ]);

        return true;
    }

    /**
     * atualizar
     * Edita titulo/descricao(anotacoes)/responsavel/prazo de uma tarefa ja
     * existente. Nao mexe em status -- isso e responsabilidade exclusiva de
     * moverStatus() (RN04).
     *
     * @param int $id
     * @param array $dados
     * @return bool
     */
    public function atualizar(int $id, array $dados): bool
    {
        $dadosPermitidos = array_intersect_key($dados, array_flip(self::CAMPOS_EDITAVEIS));

        if (empty($dadosPermitidos)) {
            return false;
        }

        $sets   = [];
        $params = ['id' => $id];

        foreach ($dadosPermitidos as $campo => $valor) {
            $sets[] = "{$campo} = :{$campo}";
            $params[$campo] = $valor;
        }

        $sql = "UPDATE tarefas SET " . implode(', ', $sets) . " WHERE id = :id";
        $this->connDb->update($sql, $params);

        return true;
    }

    /**
     * excluir
     * Exclui a tarefa do projeto.
     *
     * @param int $tarefaId
     * @return bool
     */
    public function excluir(int $tarefaId): bool
    {
        $sql = "DELETE FROM tarefas WHERE id = :id";
        return $this->connDb->delete($sql, ['id' => $tarefaId]) > 0;
    }
}
