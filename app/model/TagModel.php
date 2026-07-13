<?php

namespace App\Model;

class TagModel extends BaseModel
{
    protected $validationRules = [
        'nome' => ['rules' => 'required|min:1|max:50', 'label' => 'Tag'],
    ];

    /**
     * buscarOuCriar
     * RN07 libera tags mesmo em transacoes importadas -- por isso a tag em
     * si e sempre "achar ou criar", nunca falha por duplicidade.
     *
     * @param string $nome
     * @param int $usuarioId
     * @return int
     */
    public function buscarOuCriar(string $nome, int $usuarioId): int
    {
        $sql = "SELECT id FROM tags WHERE usuario_id = :usuario_id AND nome = :nome LIMIT 1";
        $existente = $this->connDb->select($sql, ['usuario_id' => $usuarioId, 'nome' => $nome], 'one');

        if (count($existente) > 0) {
            return (int) $existente['id'];
        }

        $sqlInsert = "INSERT INTO tags (usuario_id, nome) VALUES (:usuario_id, :nome)";
        return $this->connDb->insert($sqlInsert, ['usuario_id' => $usuarioId, 'nome' => $nome]);
    }

    /**
     * listarPorUsuario
     *
     * @param int $usuarioId
     * @return array
     */
    public function listarPorUsuario(int $usuarioId): array
    {
        $sql = "SELECT * FROM tags WHERE usuario_id = :usuario_id ORDER BY nome ASC";
        return $this->connDb->select($sql, ['usuario_id' => $usuarioId]);
    }

    /**
     * vincularATransacao
     *
     * @param int $transacaoId
     * @param int $tagId
     * @return void
     */
    public function vincularATransacao(int $transacaoId, int $tagId): void
    {
        $sql = "INSERT IGNORE INTO transacao_tags (transacao_id, tag_id) VALUES (:transacao_id, :tag_id)";
        $this->connDb->insert($sql, ['transacao_id' => $transacaoId, 'tag_id' => $tagId]);
    }

    /**
     * desvincular
     *
     * @param int $transacaoId
     * @param int $tagId
     * @return void
     */
    public function desvincular(int $transacaoId, int $tagId): void
    {
        $sql = "DELETE FROM transacao_tags WHERE transacao_id = :transacao_id AND tag_id = :tag_id";
        $this->connDb->delete($sql, ['transacao_id' => $transacaoId, 'tag_id' => $tagId]);
    }

    /**
     * listarPorTransacao
     *
     * @param int $transacaoId
     * @return array
     */
    public function listarPorTransacao(int $transacaoId): array
    {
        $sql = "SELECT t.* FROM tags t
                INNER JOIN transacao_tags tt ON tt.tag_id = t.id
                WHERE tt.transacao_id = :transacao_id
                ORDER BY t.nome ASC";

        return $this->connDb->select($sql, ['transacao_id' => $transacaoId]);
    }
}
