<?php

namespace App\Model;

class TermoModel extends BaseModel
{
    protected $tipos = [
        'termos_uso' => 'Termos de Uso',
        'politica_privacidade' => 'Política de Privacidade',
    ];

    public function listarTodos(): array
    {
        $sql = "SELECT * FROM termos ORDER BY tipo ASC, ativo DESC, criado_em DESC";
        return $this->connDb->select($sql);
    }

    public function listarAtivos(): array
    {
        $sql = "SELECT * FROM termos WHERE ativo = 1 ORDER BY tipo ASC, criado_em DESC";
        return $this->connDb->select($sql);
    }

    public function buscarPorId(int $id): array
    {
        $sql = "SELECT * FROM termos WHERE id = :id LIMIT 1";
        return $this->connDb->select($sql, ['id' => $id], 'one');
    }

    public function buscarAtivosNaoAceitos(int $usuarioId): array
    {
        $sql = "SELECT t.*
                FROM termos t
                WHERE t.ativo = 1
                  AND NOT EXISTS (
                      SELECT 1
                      FROM usuario_aceite_termos uat
                      WHERE uat.termo_id = t.id
                        AND uat.usuario_id = :usuario_id
                  )
                ORDER BY t.tipo ASC, t.criado_em DESC";

        return $this->connDb->select($sql, ['usuario_id' => $usuarioId]);
    }

    public function usuarioAceitouTodosAtivos(int $usuarioId): bool
    {
        $sql = "SELECT COUNT(*) AS pendentes
                FROM termos t
                WHERE t.ativo = 1
                  AND NOT EXISTS (
                      SELECT 1
                      FROM usuario_aceite_termos uat
                      WHERE uat.termo_id = t.id
                        AND uat.usuario_id = :usuario_id
                  )";

        $resultado = $this->connDb->select($sql, ['usuario_id' => $usuarioId], 'one');
        return ((int) ($resultado['pendentes'] ?? 0)) === 0;
    }

    public function inserir(array $dados): int
    {
        $sql = "INSERT INTO termos (tipo, titulo, conteudo, versao, ativo)
                VALUES (:tipo, :titulo, :conteudo, :versao, :ativo)";

        return $this->connDb->insert($sql, [
            'tipo' => $dados['tipo'],
            'titulo' => $dados['titulo'],
            'conteudo' => $dados['conteudo'],
            'versao' => $dados['versao'],
            'ativo' => $dados['ativo'] ? 1 : 0,
        ]);
    }

    public function ativar(int $id): int
    {
        $sql = "UPDATE termos SET ativo = 1 WHERE id = :id";
        return $this->connDb->update($sql, ['id' => $id]);
    }

    public function desativar(int $id): int
    {
        $sql = "UPDATE termos SET ativo = 0 WHERE id = :id";
        return $this->connDb->update($sql, ['id' => $id]);
    }

    public function desativarAtivosPorTipo(string $tipo): int
    {
        $sql = "UPDATE termos SET ativo = 0 WHERE tipo = :tipo AND ativo = 1";
        return $this->connDb->update($sql, ['tipo' => $tipo]);
    }

    public function aceitarTermos(int $usuarioId, array $termoIds, ?string $ip = null, ?string $userAgent = null): int
    {
        $aceites = 0;

        foreach ($termoIds as $termoId) {
            $termoId = (int) $termoId;
            if ($termoId === 0) {
                continue;
            }

            if ($this->usuarioAceitouTermo($usuarioId, $termoId)) {
                continue;
            }

            $sql = "INSERT INTO usuario_aceite_termos (usuario_id, termo_id, ip, user_agent)
                    VALUES (:usuario_id, :termo_id, :ip, :user_agent)";

            $insertId = $this->connDb->insert($sql, [
                'usuario_id' => $usuarioId,
                'termo_id' => $termoId,
                'ip' => $ip,
                'user_agent' => $userAgent,
            ]);

            if ($insertId > 0) {
                $aceites++;
            }
        }

        return $aceites;
    }

    public function usuarioAceitouTermo(int $usuarioId, int $termoId): bool
    {
        $sql = "SELECT id FROM usuario_aceite_termos WHERE usuario_id = :usuario_id AND termo_id = :termo_id LIMIT 1";
        return count($this->connDb->select($sql, ['usuario_id' => $usuarioId, 'termo_id' => $termoId], 'one')) > 0;
    }
}
