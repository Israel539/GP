<?php

namespace App\Model;

class ContaModel extends BaseModel
{
    protected $validationRules = [
        'nome' => ['rules' => 'required|min:2|max:100', 'label' => 'Nome da conta'],
    ];

    /**
     * criar
     *
     * @param array $dados ('nome', 'tipo'?, 'saldo_inicial'?, 'instituicao'?)
     * @param int $usuarioId
     * @return int
     */
    public function criar(array $dados, int $usuarioId): int
    {
        $sql = "INSERT INTO contas (usuario_id, nome, tipo, saldo_inicial, instituicao, pluggy_account_id)
                VALUES (:usuario_id, :nome, :tipo, :saldo_inicial, :instituicao, :pluggy_account_id)";

        return $this->connDb->insert($sql, [
            'usuario_id'        => $usuarioId,
            'nome'              => $dados['nome'],
            'tipo'              => $dados['tipo'] ?? 'corrente',
            'saldo_inicial'     => $dados['saldo_inicial'] ?? 0,
            'instituicao'       => $dados['instituicao'] ?? null,
            'pluggy_account_id' => $dados['pluggy_account_id'] ?? null,
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
        $sql = "SELECT * FROM contas WHERE id = :id LIMIT 1";
        return $this->connDb->select($sql, ['id' => $id], 'one');
    }

    /**
     * listarPorUsuario
     * RN08: cada linha ja vem com o saldo atual calculado (vw_saldo_contas),
     * nunca de uma coluna armazenada.
     *
     * @param int $usuarioId
     * @param bool $isAdmin
     * @return array
     */
    public function listarPorUsuario(int $usuarioId, bool $isAdmin = false): array
    {
        if ($isAdmin) {
            $sql = "SELECT c.*, v.saldo_atual, u.nome AS dono_nome
                    FROM contas c
                    INNER JOIN vw_saldo_contas v ON v.conta_id = c.id
                    INNER JOIN usuarios u ON u.id = c.usuario_id
                    ORDER BY u.nome ASC, c.nome ASC";

            return $this->connDb->select($sql);
        }

        $sql = "SELECT c.*, v.saldo_atual
                FROM contas c
                INNER JOIN vw_saldo_contas v ON v.conta_id = c.id
                WHERE c.usuario_id = :usuario_id
                ORDER BY c.nome ASC";

        return $this->connDb->select($sql, ['usuario_id' => $usuarioId]);
    }

    /**
     * saldoAtual
     * RN08: saldo em tempo real, via view (saldo_inicial + receitas - despesas
     * confirmadas, exceto modalidade credito -- ver RN09).
     *
     * @param int $contaId
     * @return float
     */
    public function saldoAtual(int $contaId): float
    {
        $sql = "SELECT saldo_atual FROM vw_saldo_contas WHERE conta_id = :conta_id LIMIT 1";
        $linha = $this->connDb->select($sql, ['conta_id' => $contaId], 'one');

        return isset($linha['saldo_atual']) ? (float) $linha['saldo_atual'] : 0.0;
    }

    /**
     * usuarioEhDono
     * Checagem de autorizacao antes de qualquer operacao na conta.
     *
     * @param int $contaId
     * @param int $usuarioId
     * @return bool
     */
    public function usuarioEhDono(int $contaId, int $usuarioId): bool
    {
        $conta = $this->buscarPorId($contaId);
        return count($conta) > 0 && (int) $conta['usuario_id'] === $usuarioId;
    }
}
