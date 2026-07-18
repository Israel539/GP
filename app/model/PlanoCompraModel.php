<?php

namespace App\Model;

class PlanoCompraModel extends BaseModel
{
    protected $validationRules = [
        'nome' => ['rules' => 'required|min:3|max:150', 'label' => 'Nome do plano'],
        'valor_total' => ['rules' => 'required|float', 'label' => 'Valor total'],
        'parcelas_previstas' => ['rules' => 'required|int', 'label' => 'Parcelas previstas'],
    ];

    /**
     * criar
     * @param array $dados
     * @param int $usuarioId
     * @return int
     */
    public function criar(array $dados, int $usuarioId): int
    {
        $sql = "INSERT INTO planos_compra
                    (usuario_id, nome, descricao, imagem_url, produto_url,
                     valor_total, parcelas_previstas, status, data_prevista_compra)
                VALUES
                    (:usuario_id, :nome, :descricao, :imagem_url, :produto_url,
                     :valor_total, :parcelas_previstas, :status, :data_prevista_compra)";

        return $this->connDb->insert($sql, [
            'usuario_id'           => $usuarioId,
            'nome'                 => $dados['nome'],
            'descricao'            => $dados['descricao'] ?? null,
            'imagem_url'           => $dados['imagem_url'] ?? null,
            'produto_url'          => $dados['produto_url'] ?? null,
            'valor_total'          => $dados['valor_total'],
            'parcelas_previstas'   => $dados['parcelas_previstas'] ?? 1,
            'status'               => 'planejamento',
            'data_prevista_compra' => $dados['data_prevista_compra'] ?? null,
        ]);
    }

    /**
     * listarPorUsuario
     * @param int $usuarioId
     * @return array
     */
    public function listarPorUsuario(int $usuarioId, string $search = '', int $page = 1, int $perPage = 6, bool $includeExcluidos = false): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where = "usuario_id = :usuario_id";
        $params = ['usuario_id' => $usuarioId];

        if (!$includeExcluidos) {
            $where .= " AND status != 'excluido'";
        }

        if ($search !== '') {
            $where .= " AND (nome LIKE :search OR descricao LIKE :search OR produto_url LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        $limit = max(1, (int) $perPage);
        $offset = max(0, (int) $offset);

        $sql = "SELECT pc.*,
                    COALESCE((SELECT SUM(valor) FROM plano_compra_parcelas WHERE plano_compra_id = pc.id), 0) AS valor_guardado,
                    (SELECT COUNT(*) FROM plano_compra_parcelas WHERE plano_compra_id = pc.id) AS parcelas_pagas
                FROM planos_compra pc
                WHERE {$where}
                ORDER BY status ASC, atualizado_em DESC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->connDb->select($sql, $params);
    }

    public function contarPorUsuario(int $usuarioId, string $search = '', bool $includeExcluidos = false): int
    {
        $where = "usuario_id = :usuario_id";
        $params = ['usuario_id' => $usuarioId];

        if (!$includeExcluidos) {
            $where .= " AND status != 'excluido'";
        }

        if ($search !== '') {
            $where .= " AND (nome LIKE :search OR descricao LIKE :search OR produto_url LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        $sql = "SELECT COUNT(*) AS cnt FROM planos_compra WHERE {$where}";
        $row = $this->connDb->select($sql, $params, 'one');

        return isset($row['cnt']) ? (int) $row['cnt'] : 0;
    }

    /**
     * buscarPorId
     * @param int $id
     * @return array
     */
    public function buscarPorId(int $id): array
    {
        $sql = "SELECT * FROM planos_compra WHERE id = :id LIMIT 1";
        return $this->connDb->select($sql, ['id' => $id], 'one');
    }

    // Campos que o USUARIO pode alterar via formulario de edicao. Qualquer
    // outra chave que vier em $dados (ex: usuario_id, status, excluido_em,
    // criado_em, id) e ignorada aqui -- essas colunas so podem mudar atraves
    // dos metodos dedicados (finalizar(), cancelar(), deletar(), restaurar()),
    // que tem sua propria regra de negocio. Sem essa lista branca, qualquer
    // campo extra enviado no POST (ainda que nao exista no <form> HTML) virava
    // uma coluna no SET do UPDATE -- inclusive usuario_id, permitindo shift de
    // dono do registro.
    private const CAMPOS_EDITAVEIS = [
        'nome', 'descricao', 'imagem_url', 'produto_url',
        'valor_total', 'parcelas_previstas', 'data_prevista_compra',
    ];

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

        $sets = [];
        $params = ['id' => $id];

        foreach ($dadosPermitidos as $campo => $valor) {
            $sets[] = "{$campo} = :{$campo}";
            $params[$campo] = $valor;
        }

        $sql = "UPDATE planos_compra SET " . implode(', ', $sets) . " WHERE id = :id";
        $this->connDb->update($sql, $params);

        return true;
    }

    /**
     * deletar
     * NAO remove a linha do banco -- e um soft-delete: marca status='excluido'
     * e grava excluido_em, permitindo undo via restaurar() dentro da janela
     * de RESTORE_WINDOW_HOURS. (O nome do metodo e "deletar" pela API publica
     * que o Controller usa, mas o comportamento real e o soft-delete abaixo.)
     * @param int $id
     * @return bool
     */
    public function deletar(int $id): bool
    {
        $sql = "UPDATE planos_compra SET status = 'excluido', excluido_em = CURRENT_TIMESTAMP WHERE id = :id";
        return $this->connDb->update($sql, ['id' => $id]) !== false;
    }

    /**
     * contarExcluidosPorUsuario
     */
    public function contarExcluidosPorUsuario(int $usuarioId): int
    {
        $sql = "SELECT COUNT(*) AS cnt FROM planos_compra WHERE usuario_id = :usuario_id AND status = 'excluido'";
        $row = $this->connDb->select($sql, ['usuario_id' => $usuarioId], 'one');
        return isset($row['cnt']) ? (int) $row['cnt'] : 0;
    }

    /**
     * restaurar
     */
    public function restaurar(int $id): bool
    {
        $window = defined('RESTORE_WINDOW_HOURS') ? (int) RESTORE_WINDOW_HOURS : 24;
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$window} hours"));

        $sql = "UPDATE planos_compra SET status = 'planejamento', excluido_em = NULL WHERE id = :id AND status = 'excluido' AND excluido_em >= :cutoff";
        return $this->connDb->update($sql, ['id' => $id, 'cutoff' => $cutoff]) !== false;
    }

    /**
     * restaurarTodosPorUsuario
     * Restaura todos os planos excluidos de um usuario dentro da janela de undo.
     */
    public function restaurarTodosPorUsuario(int $usuarioId): bool
    {
        $window = defined('RESTORE_WINDOW_HOURS') ? (int) RESTORE_WINDOW_HOURS : 24;
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$window} hours"));

        $sql = "UPDATE planos_compra
                SET status = 'planejamento', excluido_em = NULL
                WHERE usuario_id = :usuario_id
                AND status = 'excluido'
                AND excluido_em >= :cutoff";

        return $this->connDb->update($sql, ['usuario_id' => $usuarioId, 'cutoff' => $cutoff]) !== false;
    }

    /**
     * finalizar
     * Marca o plano como concluido e grava data de conclusao.
     * @param int $id
     * @return bool
     */
    public function finalizar(int $id): bool
    {
        $sql = "UPDATE planos_compra
                SET status = 'concluido', data_conclusao = CURRENT_DATE()
                WHERE id = :id";

        return $this->connDb->update($sql, ['id' => $id]) > 0;
    }

    /**
     * cancelar
     * @param int $id
     * @return bool
     */
    public function cancelar(int $id): bool
    {
        $sql = "UPDATE planos_compra SET status = 'cancelado' WHERE id = :id";
        return $this->connDb->update($sql, ['id' => $id]) > 0;
    }

    /**
     * atualizarProgresso
     * Chamado toda vez que uma parcela e adicionada/removida. Compara o
     * quanto ja foi guardado (soma de plano_compra_parcelas) com a meta
     * (valor_total) e transiciona o status automaticamente:
     *   planejamento -> em_andamento (assim que a 1a parcela entra)
     *   em_andamento -> concluido (quando o guardado atinge a meta)
     * Nao mexe em planos ja cancelados/excluidos -- essa automacao nao deve
     * reviver um plano que o usuario descartou de proposito.
     *
     * @param int $id
     * @return void
     */
    public function atualizarProgresso(int $id): void
    {
        $plano = $this->buscarPorId($id);

        if (count($plano) === 0 || in_array($plano['status'], ['cancelado', 'excluido'], true)) {
            return;
        }

        $sql = "SELECT COALESCE(SUM(valor), 0) AS total FROM plano_compra_parcelas WHERE plano_compra_id = :id";
        $guardado = (float) $this->connDb->select($sql, ['id' => $id], 'one')['total'];

        if ($guardado >= (float) $plano['valor_total'] && $plano['status'] !== 'concluido') {
            $this->finalizar($id);
        } elseif ($guardado > 0 && $plano['status'] === 'planejamento') {
            $this->connDb->update("UPDATE planos_compra SET status = 'em_andamento' WHERE id = :id", ['id' => $id]);
        }
    }
}
