<?php

namespace App\Model;

class TarefaModel extends BaseModel
{
    const STATUS_A_FAZER      = 'a_fazer';
    const STATUS_EM_ANDAMENTO = 'em_andamento';
    const STATUS_CONCLUIDO    = 'concluido';

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
            $tarefa['atrasada'] = $this->calcularAtraso($tarefa);
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
        if (empty($tarefa['data_limite']) || $tarefa['status'] === self::STATUS_CONCLUIDO) {
            return false;
        }

        $dataLimite = \DateTime::createFromFormat('Y-m-d', $tarefa['data_limite']);
        if (!($dataLimite instanceof \DateTime) || $dataLimite->format('Y-m-d') !== $tarefa['data_limite']) {
            return false; // datas inválidas ou ZERO-Date não são consideradas atrasadas
        }

        return $dataLimite < new \DateTime('today');
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
}
