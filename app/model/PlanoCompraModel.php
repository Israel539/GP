<?php

namespace App\Model;

class PlanoCompraModel extends BaseModel
{
    protected $validationRules = [
        'nome' => ['rules' => 'required|min:3|max:150', 'label' => 'Nome do plano'],
        'valor_total' => ['rules' => 'required|float', 'label' => 'Valor total'],
        'parcelas_previstas' => ['rules' => 'required|int', 'label' => 'Parcelas previstas'],
    ];

    // Campos editaveis via formulario. parent_id fica de fora de proposito:
    // definido so na criacao, nao reaproveitamos pra "mover" um item entre
    // categorias depois (evita loop de parentesco por enquanto).
    private const CAMPOS_EDITAVEIS = [
        'nome', 'descricao', 'imagem_url', 'produto_url',
        'valor_total', 'parcelas_previstas', 'data_prevista_compra',
    ];

    /**
     * criar
     * @param array $dados
     * @param int $usuarioId
     * @param int|null $parentId Se vier preenchido, esse plano passa a ser
     *        um "item" dentro de outro plano (categoria/pasta).
     * @return int
     */
    public function criar(array $dados, int $usuarioId, ?int $parentId = null): int
    {
        $sql = "INSERT INTO planos_compra
                    (usuario_id, parent_id, nome, descricao, imagem_url, produto_url,
                     valor_total, parcelas_previstas, status, data_prevista_compra)
                VALUES
                    (:usuario_id, :parent_id, :nome, :descricao, :imagem_url, :produto_url,
                     :valor_total, :parcelas_previstas, :status, :data_prevista_compra)";

        return $this->connDb->insert($sql, [
            'usuario_id'           => $usuarioId,
            'parent_id'            => $parentId,
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
     * Por padrao traz so os planos de topo (sem pai) -- os itens dentro de
     * uma categoria aparecem na tela de detalhe do pai, nao na lista geral.
     * @param int $usuarioId
     * @return array
     */
    public function listarPorUsuario(int $usuarioId, string $search = '', int $page = 1, int $perPage = 6, bool $includeExcluidos = false, bool $apenasRaizes = true): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where = "usuario_id = :usuario_id";
        $params = ['usuario_id' => $usuarioId];

        if ($apenasRaizes) {
            $where .= " AND parent_id IS NULL";
        }

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
                    (SELECT COUNT(*) FROM plano_compra_parcelas WHERE plano_compra_id = pc.id) AS parcelas_pagas,
                    (SELECT COUNT(*) FROM planos_compra filhos WHERE filhos.parent_id = pc.id AND filhos.status != 'excluido') AS total_filhos
                FROM planos_compra pc
                WHERE {$where}
                ORDER BY status ASC, atualizado_em DESC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->connDb->select($sql, $params);
    }

    public function contarPorUsuario(int $usuarioId, string $search = '', bool $includeExcluidos = false, bool $apenasRaizes = true): int
    {
        $where = "usuario_id = :usuario_id";
        $params = ['usuario_id' => $usuarioId];

        if ($apenasRaizes) {
            $where .= " AND parent_id IS NULL";
        }

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
     * listarFilhos
     * Itens dentro de uma categoria (ex: Micro-ondas e Geladeira dentro de
     * Cozinha), ja com o progresso de cada um calculado.
     * @param int $parentId
     * @return array
     */
    public function listarFilhos(int $parentId): array
    {
        $sql = "SELECT pc.*,
                    COALESCE((SELECT SUM(valor) FROM plano_compra_parcelas WHERE plano_compra_id = pc.id), 0) AS valor_guardado,
                    (SELECT COUNT(*) FROM plano_compra_parcelas WHERE plano_compra_id = pc.id) AS parcelas_pagas,
                    (SELECT COUNT(*) FROM planos_compra netos WHERE netos.parent_id = pc.id AND netos.status != 'excluido') AS total_filhos
                FROM planos_compra pc
                WHERE parent_id = :parent_id AND status != 'excluido'
                ORDER BY status ASC, atualizado_em DESC";

        return $this->connDb->select($sql, ['parent_id' => $parentId]);
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

    /**
     * totaisComFilhos
     * Soma o valor_total e o valor_guardado do plano com o de TODOS os
     * descendentes (filhos, netos, etc). E o que faz "Coisas para casa"
     * mostrar o progresso combinado de Cozinha + tudo que tiver dentro dela.
     * @param int $id
     * @return array ['valor_total' => float, 'valor_guardado' => float]
     */
    public function totaisComFilhos(int $id): array
    {
        $plano = $this->buscarPorId($id);

        if (count($plano) === 0) {
            return ['valor_total' => 0.0, 'valor_guardado' => 0.0];
        }

        $sqlGuardado = "SELECT COALESCE(SUM(valor), 0) AS total FROM plano_compra_parcelas WHERE plano_compra_id = :id";
        $totais = [
            'valor_total'    => (float) $plano['valor_total'],
            'valor_guardado' => (float) $this->connDb->select($sqlGuardado, ['id' => $id], 'one')['total'],
        ];

        foreach ($this->listarFilhos($id) as $filho) {
            $totaisFilho = $this->totaisComFilhos((int) $filho['id']);
            $totais['valor_total']    += $totaisFilho['valor_total'];
            $totais['valor_guardado'] += $totaisFilho['valor_guardado'];
        }

        return $totais;
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
     * Soft-delete: marca status='excluido', permite restaurar depois.
     * @param int $id
     * @return bool
     */
    public function deletar(int $id): bool
    {
        $sql = "UPDATE planos_compra SET status = 'excluido', excluido_em = CURRENT_TIMESTAMP WHERE id = :id";
        return $this->connDb->update($sql, ['id' => $id]) !== false;
    }

    public function contarExcluidosPorUsuario(int $usuarioId): int
    {
        $sql = "SELECT COUNT(*) AS cnt FROM planos_compra WHERE usuario_id = :usuario_id AND status = 'excluido'";
        $row = $this->connDb->select($sql, ['usuario_id' => $usuarioId], 'one');
        return isset($row['cnt']) ? (int) $row['cnt'] : 0;
    }

    public function restaurar(int $id): bool
    {
        $window = defined('RESTORE_WINDOW_HOURS') ? (int) RESTORE_WINDOW_HOURS : 24;
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$window} hours"));

        $sql = "UPDATE planos_compra SET status = 'planejamento', excluido_em = NULL WHERE id = :id AND status = 'excluido' AND excluido_em >= :cutoff";
        return $this->connDb->update($sql, ['id' => $id, 'cutoff' => $cutoff]) !== false;
    }

    /**
     * restaurarTodosPorUsuario
     * Restaura todos os planos excluidos dentro da janela de undo.
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
     * Marca o plano como concluido e grava a data.
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

    public function cancelar(int $id): bool
    {
        $sql = "UPDATE planos_compra SET status = 'cancelado' WHERE id = :id";
        return $this->connDb->update($sql, ['id' => $id]) > 0;
    }

    /**
     * atualizarProgresso
     * Roda a cada parcela adicionada/removida. Compara o guardado com a
     * meta e muda o status sozinho: planejamento -> em_andamento (primeira
     * parcela), em_andamento -> concluido (bateu a meta). Ignora planos
     * cancelados/excluidos, pra nao reviver algo que o usuario descartou.
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