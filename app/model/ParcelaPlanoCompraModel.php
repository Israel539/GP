<?php

namespace App\Model;

class ParcelaPlanoCompraModel extends BaseModel
{
    protected $validationRules = [
        'valor'          => ['rules' => 'required|numeric', 'label' => 'Valor'],
        'data_pagamento' => ['rules' => 'required',         'label' => 'Data'],
    ];

    /**
     * criar
     *
     * @param array $dados ('valor', 'data_pagamento', 'observacao'?)
     * @param int $planoCompraId
     * @return int
     */
    public function criar(array $dados, int $planoCompraId): int
    {
        $sql = "INSERT INTO plano_compra_parcelas (plano_compra_id, valor, data_pagamento, observacao)
                VALUES (:plano_compra_id, :valor, :data_pagamento, :observacao)";

        return $this->connDb->insert($sql, [
            'plano_compra_id' => $planoCompraId,
            'valor'           => $dados['valor'],
            'data_pagamento'  => $dados['data_pagamento'],
            'observacao'      => $dados['observacao'] ?? null,
        ]);
    }

    /**
     * listarPorPlano
     *
     * @param int $planoCompraId
     * @return array
     */
    public function listarPorPlano(int $planoCompraId): array
    {
        $sql = "SELECT * FROM plano_compra_parcelas
                WHERE plano_compra_id = :plano_compra_id
                ORDER BY data_pagamento ASC, id ASC";

        return $this->connDb->select($sql, ['plano_compra_id' => $planoCompraId]);
    }

    /**
     * somarPorPlano
     * Quanto ja foi guardado ate agora para este plano.
     *
     * @param int $planoCompraId
     * @return float
     */
    public function somarPorPlano(int $planoCompraId): float
    {
        $sql = "SELECT COALESCE(SUM(valor), 0) AS total FROM plano_compra_parcelas WHERE plano_compra_id = :plano_compra_id";
        $linha = $this->connDb->select($sql, ['plano_compra_id' => $planoCompraId], 'one');
        return (float) ($linha['total'] ?? 0);
    }

    /**
     * contarPorPlano
     *
     * @param int $planoCompraId
     * @return int
     */
    public function contarPorPlano(int $planoCompraId): int
    {
        $sql = "SELECT COUNT(*) AS total FROM plano_compra_parcelas WHERE plano_compra_id = :plano_compra_id";
        $linha = $this->connDb->select($sql, ['plano_compra_id' => $planoCompraId], 'one');
        return (int) ($linha['total'] ?? 0);
    }

    /**
     * buscarPorId
     *
     * @param int $id
     * @return array
     */
    public function buscarPorId(int $id): array
    {
        $sql = "SELECT * FROM plano_compra_parcelas WHERE id = :id LIMIT 1";
        return $this->connDb->select($sql, ['id' => $id], 'one');
    }

    /**
     * excluir
     *
     * @param int $id
     * @return void
     */
    public function excluir(int $id): void
    {
        $sql = "DELETE FROM plano_compra_parcelas WHERE id = :id";
        $this->connDb->delete($sql, ['id' => $id]);
    }

    /**
     * pertenceAoPlano
     * Checagem de posse -- usada pelo Controller antes de excluir uma parcela,
     * pra nao deixar excluir a parcela de um plano que nao e do usuario.
     *
     * @param int $parcelaId
     * @param int $planoCompraId
     * @return bool
     */
    public function pertenceAoPlano(int $parcelaId, int $planoCompraId): bool
    {
        $parcela = $this->buscarPorId($parcelaId);
        return count($parcela) > 0 && (int) $parcela['plano_compra_id'] === $planoCompraId;
    }
}
