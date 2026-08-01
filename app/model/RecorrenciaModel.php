<?php

namespace App\Model;

class RecorrenciaModel extends BaseModel
{
    protected $validationRules = [
        'descricao'   => ['rules' => 'required|min:2|max:200', 'label' => 'Descricao'],
        'valor'       => ['rules' => 'required|float',         'label' => 'Valor'],
        'dia_mes'     => ['rules' => 'required|int',           'label' => 'Dia do mes'],
        'data_inicio' => ['rules' => 'required',               'label' => 'Data de inicio'],
    ];

    private const CAMPOS_EDITAVEIS = [
        'descricao', 'valor', 'tipo', 'modalidade', 'categoria_id',
        'dia_mes', 'data_inicio', 'data_fim',
    ];

    /**
     * criar
     * @param array $dados
     * @param int $contaId
     * @return int
     */
    public function criar(array $dados, int $contaId): int
    {
        $sql = "INSERT INTO transacoes_recorrentes
                    (conta_id, categoria_id, descricao, valor, tipo, modalidade, dia_mes, data_inicio, data_fim)
                VALUES
                    (:conta_id, :categoria_id, :descricao, :valor, :tipo, :modalidade, :dia_mes, :data_inicio, :data_fim)";

        return $this->connDb->insert($sql, [
            'conta_id'     => $contaId,
            'categoria_id' => !empty($dados['categoria_id']) ? $dados['categoria_id'] : null,
            'descricao'    => $dados['descricao'],
            'valor'        => $dados['valor'],
            'tipo'         => $dados['tipo'],
            'modalidade'   => $dados['modalidade'] ?? 'outro',
            'dia_mes'      => $dados['dia_mes'],
            'data_inicio'  => $dados['data_inicio'],
            'data_fim'     => $dados['data_fim'] ?? null,
        ]);
    }

    /**
     * listarPorUsuario
     * @param int $usuarioId
     * @return array
     */
    public function listarPorUsuario(int $usuarioId): array
    {
        $sql = "SELECT r.*, c.nome AS conta_nome, cat.nome AS categoria_nome
                FROM transacoes_recorrentes r
                INNER JOIN contas c ON c.id = r.conta_id
                LEFT JOIN categorias cat ON cat.id = r.categoria_id
                WHERE c.usuario_id = :usuario_id
                ORDER BY r.ativo DESC, r.dia_mes ASC";

        return $this->connDb->select($sql, ['usuario_id' => $usuarioId]);
    }

    /**
     * buscarPorId
     * @param int $id
     * @return array
     */
    public function buscarPorId(int $id): array
    {
        $sql = "SELECT * FROM transacoes_recorrentes WHERE id = :id LIMIT 1";
        return $this->connDb->select($sql, ['id' => $id], 'one');
    }

    /**
     * pertenceAoUsuario
     * @param int $id
     * @param int $usuarioId
     * @return bool
     */
    public function pertenceAoUsuario(int $id, int $usuarioId): bool
    {
        $sql = "SELECT r.id FROM transacoes_recorrentes r
                INNER JOIN contas c ON c.id = r.conta_id
                WHERE r.id = :id AND c.usuario_id = :usuario_id
                LIMIT 1";

        $linha = $this->connDb->select($sql, ['id' => $id, 'usuario_id' => $usuarioId], 'one');
        return count($linha) > 0;
    }

    /**
     * atualizar
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

        if (array_key_exists('categoria_id', $dadosPermitidos) && empty($dadosPermitidos['categoria_id'])) {
            $dadosPermitidos['categoria_id'] = null;
        }
        if (array_key_exists('data_fim', $dadosPermitidos) && empty($dadosPermitidos['data_fim'])) {
            $dadosPermitidos['data_fim'] = null;
        }

        $sets = [];
        $params = ['id' => $id];

        foreach ($dadosPermitidos as $campo => $valor) {
            $sets[] = "{$campo} = :{$campo}";
            $params[$campo] = $valor;
        }

        $sql = "UPDATE transacoes_recorrentes SET " . implode(', ', $sets) . " WHERE id = :id";
        $this->connDb->update($sql, $params);

        return true;
    }

    /**
     * alternarAtivo
     * @param int $id
     * @param bool $ativo
     * @return void
     */
    public function alternarAtivo(int $id, bool $ativo): void
    {
        $sql = "UPDATE transacoes_recorrentes SET ativo = :ativo WHERE id = :id";
        $this->connDb->update($sql, ['ativo' => $ativo ? 1 : 0, 'id' => $id]);
    }

    /**
     * excluir
     * @param int $id
     * @return void
     */
    public function excluir(int $id): void
    {
        $sql = "DELETE FROM transacoes_recorrentes WHERE id = :id";
        $this->connDb->delete($sql, ['id' => $id]);
    }

    /**
     * listarParaGerar
     * Usado pelo cron: recorrencias ativas, dentro do periodo de vigencia,
     * cujo dia_mes ja chegou este mes e que AINDA NAO geraram a transacao
     * deste mes (ultima_geracao nao e este mes) -- evita duplicar se o cron
     * rodar mais de uma vez no mesmo dia.
     *
     * @return array
     */
    public function listarParaGerar(): array
    {
        $sql = "SELECT * FROM transacoes_recorrentes
                WHERE ativo = 1
                  AND data_inicio <= CURDATE()
                  AND (data_fim IS NULL OR data_fim >= CURDATE())
                  AND dia_mes <= DAY(CURDATE())
                  AND (ultima_geracao IS NULL
                       OR MONTH(ultima_geracao) != MONTH(CURDATE())
                       OR YEAR(ultima_geracao) != YEAR(CURDATE()))";

        return $this->connDb->select($sql);
    }

    /**
     * marcarGerada
     * @param int $id
     * @return void
     */
    public function marcarGerada(int $id): void
    {
        $sql = "UPDATE transacoes_recorrentes SET ultima_geracao = CURDATE() WHERE id = :id";
        $this->connDb->update($sql, ['id' => $id]);
    }
}
