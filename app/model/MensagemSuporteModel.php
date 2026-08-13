<?php

namespace App\Model;

class MensagemSuporteModel extends BaseModel
{
    /**
     * enviar
     *
     * @param int $logId
     * @param int $autorId
     * @param string $mensagem
     * @return int Id da mensagem criada
     */
    public function enviar(int $logId, int $autorId, string $mensagem): int
    {
        $sql = "INSERT INTO mensagens_suporte (log_acesso_id, autor_id, mensagem)
                VALUES (:log_acesso_id, :autor_id, :mensagem)";

        return $this->connDb->insert($sql, [
            'log_acesso_id' => $logId,
            'autor_id'      => $autorId,
            'mensagem'      => $mensagem,
        ]);
    }

    /**
     * buscarPorId
     * Usado logo apos enviar(), pra devolver pro navegador a mensagem ja
     * com o nome do autor e o horario que o banco gravou.
     *
     * @param int $id
     * @return array
     */
    public function buscarPorId(int $id): array
    {
        $sql = "SELECT m.*, u.nome AS autor_nome
                FROM mensagens_suporte m
                INNER JOIN usuarios u ON u.id = m.autor_id
                WHERE m.id = :id
                LIMIT 1";

        return $this->connDb->select($sql, ['id' => $id], 'one');
    }

    /**
     * listarNovasDesde
     * Usado pelo polling do widget de chat -- so traz mensagens com id
     * maior que o ultimo que o navegador ja tem, pra nao reprocessar a
     * conversa inteira a cada ciclo (a cada poucos segundos).
     *
     * @param int $logId
     * @param int $ultimoIdConhecido
     * @return array
     */
    public function listarNovasDesde(int $logId, int $ultimoIdConhecido): array
    {
        $sql = "SELECT m.*, u.nome AS autor_nome
                FROM mensagens_suporte m
                INNER JOIN usuarios u ON u.id = m.autor_id
                WHERE m.log_acesso_id = :log_acesso_id
                  AND m.id > :ultimo_id
                ORDER BY m.id ASC";

        return $this->connDb->select($sql, [
            'log_acesso_id' => $logId,
            'ultimo_id'     => $ultimoIdConhecido,
        ]);
    }
}
