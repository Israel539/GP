<?php

namespace App\Model;

class CartaoCreditoModel extends BaseModel
{
    protected $validationRules = [
        'nome'           => ['rules' => 'required|min:2|max:100', 'label' => 'Nome do cartao'],
        'dia_fechamento' => ['rules' => 'required|int',           'label' => 'Dia de fechamento'],
        'dia_vencimento' => ['rules' => 'required|int',           'label' => 'Dia de vencimento'],
    ];

    /**
     * criar
     *
     * @param array $dados ('nome', 'limite'?, 'dia_fechamento', 'dia_vencimento')
     * @param int $contaPagadoraId
     * @return int
     */
    public function criar(array $dados, int $contaPagadoraId): int
    {
        $sql = "INSERT INTO cartoes_credito (conta_pagadora_id, nome, limite, dia_fechamento, dia_vencimento)
                VALUES (:conta_pagadora_id, :nome, :limite, :dia_fechamento, :dia_vencimento)";

        return $this->connDb->insert($sql, [
            'conta_pagadora_id' => $contaPagadoraId,
            'nome'              => $dados['nome'],
            'limite'            => $dados['limite'] ?? null,
            'dia_fechamento'    => $dados['dia_fechamento'],
            'dia_vencimento'    => $dados['dia_vencimento'],
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
        $sql = "SELECT * FROM cartoes_credito WHERE id = :id AND excluido_em IS NULL LIMIT 1";
        return $this->connDb->select($sql, ['id' => $id], 'one');
    }

    /**
     * listarPorUsuario
     * Cartao nao guarda usuario_id direto -- pertence a uma conta, que
     * pertence a um usuario. Por isso o JOIN.
     *
     * @param int $usuarioId
     * @return array
     */
    public function listarPorUsuario(int $usuarioId): array
    {
        $sql = "SELECT cc.*
                FROM cartoes_credito cc
                INNER JOIN contas c ON c.id = cc.conta_pagadora_id
                                WHERE c.usuario_id = :usuario_id
                                    AND cc.excluido_em IS NULL
                ORDER BY cc.nome ASC";

        return $this->connDb->select($sql, ['usuario_id' => $usuarioId]);
    }

    /**
     * usuarioEhDono
     *
     * @param int $cartaoId
     * @param int $usuarioId
     * @return bool
     */
    public function usuarioEhDono(int $cartaoId, int $usuarioId): bool
    {
        $sql = "SELECT cc.id
                FROM cartoes_credito cc
                INNER JOIN contas c ON c.id = cc.conta_pagadora_id
                                WHERE cc.id = :cartao_id AND c.usuario_id = :usuario_id
                                    AND cc.excluido_em IS NULL
                LIMIT 1";

        $linha = $this->connDb->select($sql, ['cartao_id' => $cartaoId, 'usuario_id' => $usuarioId], 'one');
        return count($linha) > 0;
    }

    /**
     * atualizar
     *
     * @param int $id
     * @param array $dados
     * @param int $contaPagadoraId
     * @return bool
     */
    public function atualizar(int $id, array $dados, int $contaPagadoraId): bool
    {
        $sql = "UPDATE cartoes_credito SET conta_pagadora_id = :conta_pagadora_id, nome = :nome, limite = :limite, dia_fechamento = :dia_fechamento, dia_vencimento = :dia_vencimento WHERE id = :id";

        return $this->connDb->update($sql, [
            'conta_pagadora_id' => $contaPagadoraId,
            'nome'              => $dados['nome'],
            'limite'            => $dados['limite'] ?? null,
            'dia_fechamento'    => $dados['dia_fechamento'],
            'dia_vencimento'    => $dados['dia_vencimento'],
            'id'                => $id,
        ]);
    }

    /**
     * deletar
     *
     * @param int $id
     * @return bool
     */
    public function deletar(int $id): bool
    {
        $sql = "UPDATE cartoes_credito SET excluido_em = :excluido_em WHERE id = :id";
        return $this->connDb->update($sql, ['excluido_em' => date('Y-m-d H:i:s'), 'id' => $id]);
    }
}
