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
        $sql = "SELECT * FROM contatos WHERE usuario_id = :usuario_id ORDER BY criado_em DESC";
        return $this->connDb->select($sql, ['usuario_id' => $usuarioId]);
    }

    public function listarTodos(): array
    {
        $sql = "SELECT c.*, u.nome AS nome_usuario, a.nome AS admin_nome
                FROM contatos c
                LEFT JOIN usuarios u ON u.id = c.usuario_id
                LEFT JOIN usuarios a ON a.id = c.respondido_por
                ORDER BY c.respondido_em IS NULL DESC, c.criado_em DESC";
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
}
