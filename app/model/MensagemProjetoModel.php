<?php

namespace App\Model;

class MensagemProjetoModel extends BaseModel
{
    protected $validationRules = [
        'mensagem' => ['rules' => 'required|max:2000', 'label' => 'Mensagem'],
    ];

    /**
     * enviar
     *
     * @param int $projetoId
     * @param int $usuarioId
     * @param string $mensagem
     * @return int
     */
    public function enviar(int $projetoId, int $usuarioId, string $mensagem): int
    {
        $sql = "INSERT INTO mensagens_projeto (projeto_id, usuario_id, mensagem)
                VALUES (:projeto_id, :usuario_id, :mensagem)";

        return $this->connDb->insert($sql, [
            'projeto_id' => $projetoId,
            'usuario_id' => $usuarioId,
            'mensagem'   => $mensagem,
        ]);
    }

    /**
     * listarPorProjeto
     * Ordem cronológica (mais antiga primeiro), do jeito que um chat lê.
     *
     * @param int $projetoId
     * @return array
     */
    public function listarPorProjeto(int $projetoId): array
    {
        $sql = "SELECT m.*, u.nome AS autor_nome
                FROM mensagens_projeto m
                INNER JOIN usuarios u ON u.id = m.usuario_id
                WHERE m.projeto_id = :projeto_id
                ORDER BY m.enviado_em ASC, m.id ASC";

        return $this->connDb->select($sql, ['projeto_id' => $projetoId]);
    }
}
