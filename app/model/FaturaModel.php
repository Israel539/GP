<?php

namespace App\Model;

class FaturaModel extends BaseModel
{
    const STATUS_ABERTA  = 'aberta';
    const STATUS_FECHADA = 'fechada';
    const STATUS_PAGA    = 'paga';

    /**
     * buscarOuCriarAberta
     * RN09: toda transacao de credito precisa cair numa fatura do mes certo.
     * Se a fatura do mes ja existe, reaproveita; senao, cria uma nova.
     *
     * @param int $cartaoId
     * @param string $mesReferencia Qualquer data do mes desejado (normaliza pro dia 01)
     * @return int Id da fatura
     */
    public function buscarOuCriarAberta(int $cartaoId, string $mesReferencia): int
    {
        $primeiroDia = date('Y-m-01', strtotime($mesReferencia));

        $sql = "SELECT id FROM faturas WHERE cartao_id = :cartao_id AND mes_referencia = :mes LIMIT 1";
        $existente = $this->connDb->select($sql, ['cartao_id' => $cartaoId, 'mes' => $primeiroDia], 'one');

        if (count($existente) > 0) {
            return (int) $existente['id'];
        }

        $cartaoModel = new CartaoCreditoModel();
        $cartao      = $cartaoModel->buscarPorId($cartaoId);

        // Calcula o vencimento a partir do dia_vencimento configurado no cartao.
        // Para meses com menos dias que o configurado (ex: dia 31 em fevereiro),
        // o mktime() normaliza automaticamente para o inicio do mes seguinte --
        // aceitavel para o MVP, mas vale revisar se isso gerar vencimento errado
        // em algum caso de uso real.
        $ano  = (int) date('Y', strtotime($primeiroDia));
        $mes  = (int) date('m', strtotime($primeiroDia));
        $dataVencimento = date('Y-m-d', mktime(0, 0, 0, $mes, (int) $cartao['dia_vencimento'], $ano));

        $sqlInsert = "INSERT INTO faturas (cartao_id, mes_referencia, valor_total, status, data_vencimento)
                      VALUES (:cartao_id, :mes, 0, :status, :data_vencimento)";

        return $this->connDb->insert($sqlInsert, [
            'cartao_id'       => $cartaoId,
            'mes'             => $primeiroDia,
            'status'          => self::STATUS_ABERTA,
            'data_vencimento' => $dataVencimento,
        ]);
    }

    /**
     * adicionarValor
     * Soma o valor de uma transacao de credito ao total da fatura.
     *
     * @param int $faturaId
     * @param float $valor
     * @return void
     */
    public function adicionarValor(int $faturaId, float $valor): void
    {
        $sql = "UPDATE faturas SET valor_total = valor_total + :valor WHERE id = :id";
        $this->connDb->update($sql, ['valor' => $valor, 'id' => $faturaId]);
    }

    /**
     * usuarioEhDono
     * Fatura -> Cartao -> Conta -> Usuario, por isso o duplo JOIN.
     *
     * @param int $faturaId
     * @param int $usuarioId
     * @return bool
     */
    public function usuarioEhDono(int $faturaId, int $usuarioId): bool
    {
        $sql = "SELECT f.id
                FROM faturas f
                INNER JOIN cartoes_credito cc ON cc.id = f.cartao_id
                INNER JOIN contas c ON c.id = cc.conta_pagadora_id
                WHERE f.id = :fatura_id AND c.usuario_id = :usuario_id
                LIMIT 1";

        $linha = $this->connDb->select($sql, ['fatura_id' => $faturaId, 'usuario_id' => $usuarioId], 'one');
        return count($linha) > 0;
    }

    /**
     * buscarPorId
     *
     * @param int $id
     * @return array
     */
    public function buscarPorId(int $id): array
    {
        $sql = "SELECT * FROM faturas WHERE id = :id LIMIT 1";
        return $this->connDb->select($sql, ['id' => $id], 'one');
    }

    /**
     * listarPorCartao
     *
     * @param int $cartaoId
     * @return array
     */
    public function listarPorCartao(int $cartaoId): array
    {
        $sql = "SELECT * FROM faturas WHERE cartao_id = :cartao_id ORDER BY mes_referencia DESC";
        return $this->connDb->select($sql, ['cartao_id' => $cartaoId]);
    }

    /**
     * pagar
     * RN09: e so no pagamento da fatura que o valor sai de fato do saldo da
     * conta pagadora -- por isso cria uma transacao de despesa (modalidade
     * 'debito') na conta na hora de marcar a fatura como paga. Ate aqui, as
     * transacoes de credito individuais nunca tocaram o saldo da conta.
     *
     * @param int $faturaId
     * @return bool false se a fatura nao existir ou ja estiver paga
     */
    public function pagar(int $faturaId): bool
    {
        // Nota tecnica: ao contrario do ProjetoModel::criar(), esta operacao
        // NAO esta dentro de uma transacao (o BaseModel abre uma conexao nova
        // a cada chamada de connDb->insert()/update()). Para producao, vale
        // extrair uma conexao unica com beginTransaction()/commit() aqui,
        // igual foi feito no ProjetoModel, para garantir que a transacao de
        // pagamento e o status 'paga' da fatura sejam gravados atomicamente.
        $fatura = $this->buscarPorId($faturaId);

        if (count($fatura) === 0 || $fatura['status'] === self::STATUS_PAGA) {
            return false;
        }

        $cartaoModel = new CartaoCreditoModel();
        $cartao      = $cartaoModel->buscarPorId((int) $fatura['cartao_id']);

        $transacaoModel = new TransacaoModel();
        $transacaoModel->criarManual([
            'conta_id'          => $cartao['conta_pagadora_id'],
            'descricao'         => 'Pagamento fatura ' . date('m/Y', strtotime($fatura['mes_referencia'])),
            'valor'             => $fatura['valor_total'],
            'tipo'              => 'despesa',
            'modalidade'        => 'debito',
            'data_fato_gerador' => date('Y-m-d'),
            'data_competencia'  => date('Y-m-d'),
        ]);

        $sql = "UPDATE faturas SET status = :status, data_pagamento = :data_pagamento WHERE id = :id";
        $this->connDb->update($sql, [
            'status'         => self::STATUS_PAGA,
            'data_pagamento' => date('Y-m-d'),
            'id'             => $faturaId,
        ]);

        return true;
    }
}
