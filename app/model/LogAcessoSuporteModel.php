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

        $id = $this->connDb->insert($sql, [
            'admin_id'        => $adminId,
            'usuario_alvo_id' => $dono['usuario_id'],
            'tipo_recurso'    => $tipoRecurso,
            'recurso_id'      => $recursoId,
            'motivo'          => trim($motivo),
            'expira_em'       => $expiraEm,
        ]);

        return ['ok' => true, 'erro' => null, 'expiraEm' => $expiraEm, 'id' => $id];
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

    /**
     * buscarPorId
     * Usado pelo chat de suporte (SuporteChat) para conferir quem participa
     * da sessao (admin_id / usuario_alvo_id) e se ela ainda esta ativa.
     *
     * @param int $id
     * @return array
     */
    public function buscarPorId(int $id): array
    {
        $sql = "SELECT l.*, admin.nome AS admin_nome, alvo.nome AS alvo_nome
                FROM log_acesso_suporte l
                INNER JOIN usuarios admin ON admin.id = l.admin_id
                LEFT JOIN usuarios alvo ON alvo.id = l.usuario_alvo_id
                WHERE l.id = :id
                LIMIT 1";

        return $this->connDb->select($sql, ['id' => $id], 'one');
    }

    /**
     * buscarSessaoAtivaParaUsuario
     * Encontra se o USUARIO ALVO (nao o admin) esta participando de uma
     * sessao de suporte ativa agora -- e assim que a caixinha de chat
     * aparece pro lado do usuario comum, que nao tem nada disso guardado na
     * propria sessao PHP (foi o admin que iniciou o acesso).
     *
     * @param int $usuarioAlvoId
     * @return array Vazio se nao houver sessao ativa
     */
    public function buscarSessaoAtivaParaUsuario(int $usuarioAlvoId): array
    {
        $sql = "SELECT l.*, admin.nome AS admin_nome, alvo.nome AS alvo_nome
                FROM log_acesso_suporte l
                INNER JOIN usuarios admin ON admin.id = l.admin_id
                LEFT JOIN usuarios alvo ON alvo.id = l.usuario_alvo_id
                WHERE l.usuario_alvo_id = :usuario_alvo_id
                  AND l.encerrado_em IS NULL
                  AND l.expira_em > NOW()
                ORDER BY l.concedido_em DESC
                LIMIT 1";

        return $this->connDb->select($sql, ['usuario_alvo_id' => $usuarioAlvoId], 'one');
    }

    /**
     * encerrar
     * Encerra a sessao de suporte antes do prazo de expiracao (chamado pelo
     * botao "Encerrar suporte" do chat) -- so quem participa da sessao
     * (o admin que concedeu, ou o proprio usuario alvo) pode encerrar.
     *
     * @param int $logId
     * @param int $usuarioId Quem esta pedindo o encerramento
     * @return bool
     */
    public function encerrar(int $logId, int $usuarioId): bool
    {
        $sql = "UPDATE log_acesso_suporte
                SET encerrado_em = NOW()
                WHERE id = :id
                  AND encerrado_em IS NULL
                  AND (admin_id = :usuario_id OR usuario_alvo_id = :usuario_id)";

        $this->connDb->update($sql, ['id' => $logId, 'usuario_id' => $usuarioId]);

        return true;
    }

    /**
     * estaAtiva
     * Uma sessao esta ativa se ainda nao expirou E ninguem a encerrou antes
     * da hora.
     *
     * @param array $log Registro de log_acesso_suporte (ex: de buscarPorId())
     * @return bool
     */
    public function estaAtiva(array $log): bool
    {
        if (empty($log)) {
            return false;
        }

        if (!empty($log['encerrado_em'])) {
            return false;
        }

        return strtotime($log['expira_em']) > time();
    }
}
