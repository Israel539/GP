<?php

namespace App\Model;

class LogAcessoSuporteModel extends BaseModel
{
    const DURACAO_PADRAO_MINUTOS = 15;

    // De onde resolver o "dono" do recurso, para o log ficar completo mesmo
    // que o recurso_id nao exista (nesse caso, so nao preenche usuario_alvo_id).
    private const CONSULTA_DONO = [
        'projeto'      => "SELECT dono_id AS usuario_id FROM projetos WHERE id = :id",
        'conta'        => "SELECT usuario_id FROM contas WHERE id = :id",
        'cartao'       => "SELECT c.usuario_id FROM cartoes_credito cc INNER JOIN contas c ON c.id = cc.conta_pagadora_id WHERE cc.id = :id",
        'fatura'       => "SELECT c.usuario_id FROM faturas f INNER JOIN cartoes_credito cc ON cc.id = f.cartao_id INNER JOIN contas c ON c.id = cc.conta_pagadora_id WHERE f.id = :id",
        'compromisso'  => "SELECT usuario_id FROM compromissos WHERE id = :id",
        'plano_compra' => "SELECT usuario_id FROM planos_compra WHERE id = :id",
    ];

    /**
     * registrar
     * Concede acesso de suporte a um recurso especifico, por tempo limitado,
     * e grava a justificativa no log de auditoria de forma permanente (esta
     * linha nunca e apagada, nem quando o acesso expira).
     *
     * @param int $adminId
     * @param string $tipoRecurso 'projeto'|'conta'|'cartao'|'compromisso'|'plano_compra'
     * @param int $recursoId
     * @param string $motivo
     * @param int $duracaoMinutos
     * @return array ['ok' => bool, 'erro' => string|null, 'expiraEm' => string|null]
     */
    public function registrar(int $adminId, string $tipoRecurso, int $recursoId, string $motivo, int $duracaoMinutos = self::DURACAO_PADRAO_MINUTOS): array
    {
        if (!isset(self::CONSULTA_DONO[$tipoRecurso])) {
            return ['ok' => false, 'erro' => 'Tipo de recurso invalido.', 'expiraEm' => null];
        }

        if (mb_strlen(trim($motivo)) < 10) {
            return ['ok' => false, 'erro' => 'Descreva o motivo do acesso (minimo 10 caracteres) -- isso fica registrado permanentemente.', 'expiraEm' => null];
        }

        $dono = $this->connDb->select(self::CONSULTA_DONO[$tipoRecurso], ['id' => $recursoId], 'one');

        if (count($dono) === 0) {
            return ['ok' => false, 'erro' => 'Recurso nao encontrado.', 'expiraEm' => null];
        }

        $expiraEm = date('Y-m-d H:i:s', time() + ($duracaoMinutos * 60));

        $sql = "INSERT INTO log_acesso_suporte (admin_id, usuario_alvo_id, tipo_recurso, recurso_id, motivo, expira_em)
                VALUES (:admin_id, :usuario_alvo_id, :tipo_recurso, :recurso_id, :motivo, :expira_em)";

        $this->connDb->insert($sql, [
            'admin_id'        => $adminId,
            'usuario_alvo_id' => $dono['usuario_id'],
            'tipo_recurso'    => $tipoRecurso,
            'recurso_id'      => $recursoId,
            'motivo'          => trim($motivo),
            'expira_em'       => $expiraEm,
        ]);

        return ['ok' => true, 'erro' => null, 'expiraEm' => $expiraEm];
    }

    /**
     * listarHistorico
     * Transparencia: todo admin ve todo acesso de suporte ja concedido (o
     * proprio e o de outros admins), com quem, o que, e por que.
     *
     * @param int $limite
     * @return array
     */
    public function listarHistorico(int $limite = 100): array
    {
        $sql = "SELECT l.*, admin.nome AS admin_nome, alvo.nome AS alvo_nome
                FROM log_acesso_suporte l
                INNER JOIN usuarios admin ON admin.id = l.admin_id
                LEFT JOIN usuarios alvo ON alvo.id = l.usuario_alvo_id
                ORDER BY l.concedido_em DESC
                LIMIT " . (int) $limite;

        return $this->connDb->select($sql);
    }
}
