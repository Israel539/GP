<?php

namespace App\Model;

class ContaModel extends BaseModel
{
    protected $validationRules = [
        'nome' => ['rules' => 'required|min:2|max:100', 'label' => 'Nome da conta'],
    ];

    // Fragmento de SQL reutilizado em toda consulta que precisa do saldo
    // calculado de uma conta -- ANTES isso vinha de uma VIEW (vw_saldo_contas),
    // trocamos por essa sub-expressao comum porque alguns provedores de
    // hospedagem compartilhada nao liberam CREATE VIEW em contas gratuitas
    // (ver migracao 013). A regra em si nao mudou (RN08/RN09): soma receitas,
    // subtrai despesas, so movimentacoes confirmadas, ignora modalidade
    // credito (que e responsabilidade da fatura, nao da conta) e ignora
    // transacoes na lixeira (excluido_em).
    private const SQL_SALDO_ATUAL = "c.saldo_inicial + COALESCE(SUM(
                CASE
                    WHEN t.tipo = 'receita' AND t.status = 'confirmada' AND t.modalidade != 'credito' THEN t.valor
                    WHEN t.tipo = 'despesa' AND t.status = 'confirmada' AND t.modalidade != 'credito' THEN -t.valor
                    ELSE 0
                END
            ), 0)";

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
     * RN08: cada linha ja vem com o saldo atual calculado, nunca de uma
     * coluna armazenada. NAO existe mais bypass de admin aqui -- ver
     * Admin::suporteAcessar() para o fluxo auditado de acesso pontual.
     *
     * @param int $usuarioId
     * @return array
     */
    public function listarPorUsuario(int $usuarioId): array
    {
        $sql = "SELECT c.*, " . self::SQL_SALDO_ATUAL . " AS saldo_atual
                FROM contas c
                LEFT JOIN transacoes t ON t.conta_id = c.id AND t.excluido_em IS NULL
                WHERE c.usuario_id = :usuario_id
                GROUP BY c.id
                ORDER BY c.nome ASC";

        return $this->connDb->select($sql, ['usuario_id' => $usuarioId]);
    }

    /**
     * buscarContaDinheiro
     * Retorna (com saldo ja calculado) a conta que o usuario escolheu pra
     * representar o "Dinheiro Fisico" (RN10 -- ver migracao 014), ou null
     * se ele ainda nao escolheu nenhuma. Toda transacao/recorrencia com
     * modalidade='dinheiro' usa o conta_id dessa conta, nao importa em
     * qual outra conta a pessoa estava quando lancou.
     *
     * @param int $usuarioId
     * @return array|null
     */
    public function buscarContaDinheiro(int $usuarioId): ?array
    {
        $sql = "SELECT c.*, " . self::SQL_SALDO_ATUAL . " AS saldo_atual
                FROM contas c
                LEFT JOIN transacoes t ON t.conta_id = c.id AND t.excluido_em IS NULL
                WHERE c.usuario_id = :usuario_id AND c.eh_conta_dinheiro = 1
                GROUP BY c.id
                LIMIT 1";

        $linha = $this->connDb->select($sql, ['usuario_id' => $usuarioId], 'one');

        return count($linha) > 0 ? $linha : null;
    }

    /**
     * definirContaDinheiro
     * Marca $contaId como a conta que recebe lancamentos em dinheiro do
     * usuario, desmarcando qualquer outra que estivesse marcada antes --
     * so pode haver UMA por usuario. O Controller ja confere
     * usuarioEhDono($contaId, $usuarioId) antes de chamar isso.
     *
     * @param int $usuarioId
     * @param int $contaId
     * @return void
     */
    public function definirContaDinheiro(int $usuarioId, int $contaId): void
    {
        $this->connDb->update(
            "UPDATE contas SET eh_conta_dinheiro = 0 WHERE usuario_id = :usuario_id",
            ['usuario_id' => $usuarioId]
        );

        $this->connDb->update(
            "UPDATE contas SET eh_conta_dinheiro = 1 WHERE id = :id AND usuario_id = :usuario_id",
            ['id' => $contaId, 'usuario_id' => $usuarioId]
        );
    }

    /**
     * contarTodas
     * Estatistica agregada para o painel do Admin -- so o numero de contas,
     * nunca nomes ou saldos individuais.
     *
     * @return int
     */
    public function contarTodas(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM contas";
        $linha = $this->connDb->select($sql, [], 'one');
        return (int) ($linha['total'] ?? 0);
    }

    /**
     * saldoAtual
     * RN08: saldo em tempo real (saldo_inicial + receitas - despesas
     * confirmadas, exceto modalidade credito -- ver RN09).
     *
     * @param int $contaId
     * @return float
     */
    public function saldoAtual(int $contaId): float
    {
        $sql = "SELECT " . self::SQL_SALDO_ATUAL . " AS saldo_atual
                FROM contas c
                LEFT JOIN transacoes t ON t.conta_id = c.id AND t.excluido_em IS NULL
                WHERE c.id = :conta_id
                GROUP BY c.id";

        $linha = $this->connDb->select($sql, ['conta_id' => $contaId], 'one');

        return isset($linha['saldo_atual']) ? (float) $linha['saldo_atual'] : 0.0;
    }

    /**
     * ajustarSaldoAtual
     * Ajusta o saldo inicial para que o saldo calculado da conta passe a ser
     * o valor informado, sem alterar as transacoes existentes.
     *
     * @param int $contaId
     * @param float $saldoDesejado
     * @return void
     */
    public function ajustarSaldoAtual(int $contaId, float $saldoDesejado): void
    {
        $conta = $this->buscarPorId($contaId);
        $saldoAtual = $this->saldoAtual($contaId);
        $saldoInicial = (float) ($conta['saldo_inicial'] ?? 0);
        $novoSaldoInicial = $saldoInicial + ($saldoDesejado - $saldoAtual);

        $sql = "UPDATE contas SET saldo_inicial = :saldo_inicial WHERE id = :id";
        $this->connDb->update($sql, [
            'saldo_inicial' => $novoSaldoInicial,
            'id'            => $contaId,
        ]);
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

    // Campos que o dono pode editar depois de criar a conta. 'usuario_id' e
    // 'pluggy_account_id' ficam de fora de proposito -- trocar o dono ou o
    // vinculo com Open Finance nao e uma edicao de perfil da conta, e outra
    // operacao (e nem existe fluxo pra isso hoje).
    private const CAMPOS_EDITAVEIS = ['nome', 'tipo', 'saldo_inicial', 'instituicao'];

    /**
     * atualizar
     * Edita os dados cadastrais da conta. 'saldo_inicial' pode ser mudado
     * aqui sem violar a RN08 -- RN08 fala do saldo ATUAL (sempre calculado,
     * nunca gravado), nao do saldo inicial, que e um ancora legitima e
     * sempre foi um campo normal da conta.
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

        $sets = [];
        $params = ['id' => $id];

        foreach ($dadosPermitidos as $campo => $valor) {
            $sets[] = "{$campo} = :{$campo}";
            $params[$campo] = $valor;
        }

        $sql = "UPDATE contas SET " . implode(', ', $sets) . " WHERE id = :id";
        $this->connDb->update($sql, $params);

        return true;
    }

    /**
     * deletar
     * Exclusao definitiva (nao e soft-delete, ao contrario de transacoes e
     * planos de compra) -- a FK de 'transacoes' pra 'contas' e ON DELETE
     * CASCADE, entao apagar a conta apaga TODAS as transacoes dela junto,
     * de vez. O Controller exige confirmacao explicita antes de chamar
     * isso, e o texto do aviso deixa essa consequencia clara.
     *
     * @param int $id
     * @return bool
     */
    public function deletar(int $id): bool
    {
        $sql = "DELETE FROM contas WHERE id = :id";
        return $this->connDb->delete($sql, ['id' => $id]) > 0;
    }
}
