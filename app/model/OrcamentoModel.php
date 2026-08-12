<?php

namespace App\Model;

class OrcamentoModel extends BaseModel
{
    protected $validationRules = [
        'categoria_id' => ['rules' => 'required|int',   'label' => 'Categoria'],
        'valor_limite' => ['rules' => 'required|float', 'label' => 'Valor limite'],
    ];

    /**
     * definir
     * Cria o orcamento da categoria, ou atualiza o limite se ja existir um
     * pra essa categoria (so pode ter um por categoria por usuario).
     *
     * @param int $usuarioId
     * @param int $categoriaId
     * @param float $valorLimite
     * @return void
     */
    public function definir(int $usuarioId, int $categoriaId, float $valorLimite): void
    {
        $sql = "INSERT INTO orcamentos_categoria (usuario_id, categoria_id, valor_limite)
                VALUES (:usuario_id, :categoria_id, :valor_limite)
                ON DUPLICATE KEY UPDATE valor_limite = VALUES(valor_limite)";

        $this->connDb->insert($sql, [
            'usuario_id'   => $usuarioId,
            'categoria_id' => $categoriaId,
            'valor_limite' => $valorLimite,
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
        $sql = "SELECT * FROM orcamentos_categoria WHERE id = :id LIMIT 1";
        return $this->connDb->select($sql, ['id' => $id], 'one');
    }

    /**
     * jaExisteParaCategoria
     * Usado no formulario pra nao deixar cadastrar duas vezes a mesma
     * categoria (a constraint UNIQUE do banco ja impede, isso e so pra dar
     * uma mensagem de erro amigavel antes de tentar).
     *
     * @param int $usuarioId
     * @param int $categoriaId
     * @return bool
     */
    public function jaExisteParaCategoria(int $usuarioId, int $categoriaId): bool
    {
        $sql = "SELECT id FROM orcamentos_categoria WHERE usuario_id = :usuario_id AND categoria_id = :categoria_id LIMIT 1";
        $linha = $this->connDb->select($sql, ['usuario_id' => $usuarioId, 'categoria_id' => $categoriaId], 'one');
        return count($linha) > 0;
    }

    /**
     * listarComGasto
     * Cada linha ja vem com o gasto do mes atual calculado (soma de
     * despesas confirmadas dessa categoria, em qualquer conta do usuario).
     *
     * @param int $usuarioId
     * @return array
     */
    public function listarComGasto(int $usuarioId): array
    {
        $sql = "SELECT o.*, c.nome AS categoria_nome, c.cor AS categoria_cor,
                    COALESCE((
                        SELECT SUM(t.valor)
                        FROM transacoes t
                        INNER JOIN contas ct ON ct.id = t.conta_id
                        WHERE ct.usuario_id = :usuario_id
                          AND t.categoria_id = o.categoria_id
                          AND t.tipo = 'despesa'
                          AND t.status = 'confirmada'
                          AND t.excluido_em IS NULL
                          AND MONTH(t.data_fato_gerador) = MONTH(CURDATE())
                          AND YEAR(t.data_fato_gerador) = YEAR(CURDATE())
                    ), 0) AS gasto_mes
                FROM orcamentos_categoria o
                INNER JOIN categorias c ON c.id = o.categoria_id
                WHERE o.usuario_id = :usuario_id
                ORDER BY c.nome ASC";

        return $this->connDb->select($sql, ['usuario_id' => $usuarioId]);
    }

    /**
     * contarEstourados
     * Quantos orcamentos ja passaram do limite este mes -- usado no aviso
     * do dashboard da Home.
     *
     * @param int $usuarioId
     * @return int
     */
    public function contarEstourados(int $usuarioId): int
    {
        $orcamentos = $this->listarComGasto($usuarioId);
        $estourados = array_filter($orcamentos, fn($o) => (float) $o['gasto_mes'] > (float) $o['valor_limite']);

        return count($estourados);
    }

    /**
     * excluir
     * @param int $id
     * @param int $usuarioId
     * @return bool
     */
    public function excluir(int $id, int $usuarioId): bool
    {
        $sql = "DELETE FROM orcamentos_categoria WHERE id = :id AND usuario_id = :usuario_id";
        return $this->connDb->delete($sql, ['id' => $id, 'usuario_id' => $usuarioId]) > 0;
    }
}
