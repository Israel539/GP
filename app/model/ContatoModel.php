<?php

namespace App\Model;

class ContatoModel extends BaseModel
{
    protected $validationRules = [
        'nome'    => ['rules' => 'required|min:3|max:120', 'label' => 'Nome'],
        'email'   => ['rules' => 'required|email|max:150', 'label' => 'E-mail'],
        'assunto' => ['rules' => 'required|min:3|max:150', 'label' => 'Assunto'],
        'mensagem'=> ['rules' => 'required|min:5|max:2000', 'label' => 'Mensagem'],
    ];

    public function enviar(array $dados): int
    {
        $sql = "INSERT INTO contatos (usuario_id, nome, email, assunto, mensagem, status)
                VALUES (:usuario_id, :nome, :email, :assunto, :mensagem, 'pendente')";

        return $this->connDb->insert($sql, [
            'usuario_id' => $dados['usuario_id'] ?? null,
            'nome'       => $dados['nome'],
            'email'      => $dados['email'],
            'assunto'    => $dados['assunto'],
            'mensagem'   => $dados['mensagem'],
        ]);
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        $sql = "SELECT * FROM contatos WHERE usuario_id = :usuario_id AND status != 'excluido' ORDER BY criado_em DESC";
        return $this->connDb->select($sql, ['usuario_id' => $usuarioId]);
    }

    public function listarTodos(bool $mostrarExcluidos = false): array
    {
        $where = '';
        if (!$mostrarExcluidos) {
            $where = 'WHERE c.status != \'excluido\'';
        }

        $sql = "SELECT c.*, u.nome AS nome_usuario, a.nome AS admin_nome
                FROM contatos c
                LEFT JOIN usuarios u ON u.id = c.usuario_id
                LEFT JOIN usuarios a ON a.id = c.respondido_por
                {$where}
                ORDER BY c.resposta IS NULL DESC, c.criado_em DESC";
        return $this->connDb->select($sql);
    }

    public function buscarPorId(int $id): array
    {
        $sql = "SELECT c.*, u.nome AS nome_usuario, a.nome AS admin_nome
                FROM contatos c
                LEFT JOIN usuarios u ON u.id = c.usuario_id
                LEFT JOIN usuarios a ON a.id = c.respondido_por
                WHERE c.id = :id
                LIMIT 1";

        return $this->connDb->select($sql, ['id' => $id], 'one');
    }

    public function responder(int $id, int $adminId, string $resposta): bool
    {
        $sql = "UPDATE contatos
                SET resposta = :resposta,
                    respondido_por = :respondido_por,
                    respondido_em = CURRENT_TIMESTAMP,
                    status = 'respondido'
                WHERE id = :id";

        return $this->connDb->update($sql, [
            'resposta'       => $resposta,
            'respondido_por' => $adminId,
            'id'             => $id,
        ]) !== false;
    }

    /**
     * limparPorUsuario
     * Remove todas as mensagens associadas a um usuário (usado para limpar histórico)
     * @param int $usuarioId
     * @return bool
     */
    public function limparPorUsuario(int $usuarioId): bool
    {
        $sql = "UPDATE contatos SET status = 'excluido', excluido_em = CURRENT_TIMESTAMP WHERE usuario_id = :usuario_id";
        return $this->connDb->update($sql, ['usuario_id' => $usuarioId]) !== false;
    }

    /**
     * contarExcluidosPorUsuario
     * Conta quantas mensagens do usuario estao marcadas como 'excluido'
     * @param int $usuarioId
     * @return int
     */
    public function contarExcluidosPorUsuario(int $usuarioId): int
    {
        $window = defined('RESTORE_WINDOW_HOURS') ? (int) RESTORE_WINDOW_HOURS : 24;
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$window} hours"));
        $sql = "SELECT COUNT(*) AS cnt FROM contatos WHERE usuario_id = :usuario_id AND status = 'excluido' AND excluido_em >= :cutoff";
        $row = $this->connDb->select($sql, ['usuario_id' => $usuarioId, 'cutoff' => $cutoff], 'one');
        return isset($row['cnt']) ? (int) $row['cnt'] : 0;
    }

    /**
     * restaurarPorUsuario
     * Restaura (soft-undelete) mensagens marcadas como 'excluido' para 'pendente'
     * @param int $usuarioId
     * @return bool
     */
    public function restaurarPorUsuario(int $usuarioId): bool
    {
        $window = defined('RESTORE_WINDOW_HOURS') ? (int) RESTORE_WINDOW_HOURS : 24;
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$window} hours"));
        $sql = "UPDATE contatos SET status = 'pendente', excluido_em = NULL WHERE usuario_id = :usuario_id AND status = 'excluido' AND excluido_em >= :cutoff";
        return $this->connDb->update($sql, ['usuario_id' => $usuarioId, 'cutoff' => $cutoff]) !== false;
    }

    public function restaurarExcluidos(): bool
    {
        $window = defined('RESTORE_WINDOW_HOURS') ? (int) RESTORE_WINDOW_HOURS : 24;
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$window} hours"));
        $sql = "UPDATE contatos SET status = 'pendente', excluido_em = NULL WHERE status = 'excluido' AND excluido_em >= :cutoff";
        return $this->connDb->update($sql, ['cutoff' => $cutoff]) !== false;
    }
}
