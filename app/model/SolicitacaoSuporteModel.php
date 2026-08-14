<?php

namespace App\Model;

class SolicitacaoSuporteModel extends BaseModel
{
    // Mesma logica de "quem e o dono" que LogAcessoSuporteModel usa pra
    // conceder acesso -- aqui serve pra confirmar, no momento do pedido,
    // que o recurso informado realmente pertence a quem esta pedindo (ex:
    // um colaborador de projeto nao pode abrir pedido de suporte num
    // projeto que nao e dele -- so o dono pode).
    private const CONSULTA_DONO = [
        'projeto'      => "SELECT dono_id AS usuario_id FROM projetos WHERE id = :id",
        'conta'        => "SELECT usuario_id FROM contas WHERE id = :id",
        'cartao'       => "SELECT c.usuario_id FROM cartoes_credito cc INNER JOIN contas c ON c.id = cc.conta_pagadora_id WHERE cc.id = :id",
        'compromisso'  => "SELECT usuario_id FROM compromissos WHERE id = :id",
        'plano_compra' => "SELECT usuario_id FROM planos_compra WHERE id = :id",
    ];

    /**
     * criar
     * Registra o pedido de suporte do usuario -- confirma antes que o
     * recurso informado realmente pertence a ele (mesma resolucao de dono
     * que o lado do admin usa).
     *
     * @param int $usuarioId
     * @param string $tipoRecurso
     * @param int $recursoId
     * @param string $mensagem
     * @return array ['ok' => bool, 'erro' => string|null]
     */
    public function criar(int $usuarioId, string $tipoRecurso, int $recursoId, string $mensagem): array
    {
        if (!isset(self::CONSULTA_DONO[$tipoRecurso])) {
            return ['ok' => false, 'erro' => 'Selecione um tipo de recurso valido.'];
        }

        if ($recursoId <= 0) {
            return ['ok' => false, 'erro' => 'Selecione qual recurso precisa de suporte.'];
        }

        if (mb_strlen(trim($mensagem)) < 10) {
            return ['ok' => false, 'erro' => 'Descreva o problema (minimo 10 caracteres).'];
        }

        $dono = $this->connDb->select(self::CONSULTA_DONO[$tipoRecurso], ['id' => $recursoId], 'one');

        if (count($dono) === 0 || (int) $dono['usuario_id'] !== $usuarioId) {
            return ['ok' => false, 'erro' => 'Esse recurso nao pertence a voce.'];
        }

        $sql = "INSERT INTO solicitacoes_suporte (usuario_id, tipo_recurso, recurso_id, mensagem)
                VALUES (:usuario_id, :tipo_recurso, :recurso_id, :mensagem)";

        $this->connDb->insert($sql, [
            'usuario_id'   => $usuarioId,
            'tipo_recurso' => $tipoRecurso,
            'recurso_id'   => $recursoId,
            'mensagem'     => trim($mensagem),
        ]);

        return ['ok' => true, 'erro' => null];
    }

    /**
     * listarPorUsuario
     * Historico dos pedidos do proprio usuario (com status), pra ele
     * acompanhar na tela de perfil se ja foi atendido ou nao.
     *
     * @param int $usuarioId
     * @return array
     */
    public function listarPorUsuario(int $usuarioId): array
    {
        $sql = "SELECT s.*, admin.nome AS admin_nome
                FROM solicitacoes_suporte s
                LEFT JOIN usuarios admin ON admin.id = s.atendido_por_admin_id
                WHERE s.usuario_id = :usuario_id
                ORDER BY s.criado_em DESC
                LIMIT 20";

        return $this->connDb->select($sql, ['usuario_id' => $usuarioId]);
    }

    /**
     * listarPendentes
     * A fila que o admin ve em /Admin/solicitacoesSuporte -- pedidos
     * aguardando atendimento, mais antigos primeiro (quem pediu ha mais
     * tempo aparece no topo).
     *
     * @return array
     */
    public function listarPendentes(): array
    {
        $sql = "SELECT s.*, u.nome AS usuario_nome
                FROM solicitacoes_suporte s
                INNER JOIN usuarios u ON u.id = s.usuario_id
                WHERE s.status = 'pendente'
                ORDER BY s.criado_em ASC";

        return $this->connDb->select($sql);
    }

    /**
     * contarPendentes
     * Usado pra mostrar quantos pedidos estao esperando (badge no menu do admin).
     *
     * @return int
     */
    public function contarPendentes(): int
    {
        $sql   = "SELECT COUNT(*) AS total FROM solicitacoes_suporte WHERE status = 'pendente'";
        $linha = $this->connDb->select($sql, [], 'one');

        return (int) ($linha['total'] ?? 0);
    }

    /**
     * cancelar
     * O proprio usuario pode cancelar um pedido que ainda esta pendente
     * (ex: resolveu sozinho, ou abriu por engano). So funciona se o pedido
     * for dele mesmo e ainda estiver pendente -- ja atendido ou ja
     * cancelado nao muda mais.
     *
     * @param int $id
     * @param int $usuarioId
     * @return void
     */
    public function cancelar(int $id, int $usuarioId): void
    {
        $sql = "UPDATE solicitacoes_suporte
                SET status = 'cancelada'
                WHERE id = :id AND usuario_id = :usuario_id AND status = 'pendente'";

        $this->connDb->update($sql, ['id' => $id, 'usuario_id' => $usuarioId]);
    }

    /**
     * marcarAtendidaSeExistir
     * Chamado automaticamente por Admin::suporteAcessar() -- se existir um
     * pedido pendente do dono do recurso pra esse mesmo tipo/recurso, fecha
     * o ciclo sozinho (o usuario ve no proprio perfil que foi atendido, sem
     * o admin precisar fazer nada extra alem de conceder o acesso normal).
     *
     * @param string $tipoRecurso
     * @param int $recursoId
     * @param int $usuarioAlvoId
     * @param int $adminId
     * @return void
     */
    public function marcarAtendidaSeExistir(string $tipoRecurso, int $recursoId, int $usuarioAlvoId, int $adminId): void
    {
        $sql = "UPDATE solicitacoes_suporte
                SET status = 'atendida', atendido_em = NOW(), atendido_por_admin_id = :admin_id
                WHERE tipo_recurso = :tipo_recurso
                  AND recurso_id = :recurso_id
                  AND usuario_id = :usuario_id
                  AND status = 'pendente'
                ORDER BY criado_em ASC
                LIMIT 1";

        $this->connDb->update($sql, [
            'admin_id'     => $adminId,
            'tipo_recurso' => $tipoRecurso,
            'recurso_id'   => $recursoId,
            'usuario_id'   => $usuarioAlvoId,
        ]);
    }
}
