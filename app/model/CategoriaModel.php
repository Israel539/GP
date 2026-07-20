<?php

namespace App\Model;

class CategoriaModel extends BaseModel
{
    protected $validationRules = [
        'nome' => ['rules' => 'required|min:2|max:80', 'label' => 'Nome da categoria'],
    ];

    /**
     * criar
     *
     * @param array $dados ('nome', 'tipo': receita|despesa, 'icone'?, 'cor'?)
     * @param int $usuarioId
     * @return int
     */
    public function criar(array $dados, int $usuarioId): int
    {
        $sql = "INSERT INTO categorias (usuario_id, nome, tipo, icone, cor)
                VALUES (:usuario_id, :nome, :tipo, :icone, :cor)";

        return $this->connDb->insert($sql, [
            'usuario_id' => $usuarioId,
            'nome'       => $dados['nome'],
            'tipo'       => $dados['tipo'],
            'icone'      => $dados['icone'] ?? null,
            'cor'        => $dados['cor'] ?? null,
        ]);
    }

    /**
     * listarDisponiveis
     * Categorias do proprio usuario + as categorias padrao do sistema
     * (usuario_id IS NULL), filtrando por tipo se informado.
     *
     * @param int $usuarioId
     * @param string|null $tipo 'receita'|'despesa'|null (ambos)
     * @return array
     */
    public function listarDisponiveis(int $usuarioId, ?string $tipo = null): array
    {
        $sql = "SELECT * FROM categorias
                WHERE (usuario_id = :usuario_id OR usuario_id IS NULL)";

        $params = ['usuario_id' => $usuarioId];

        if ($tipo !== null) {
            $sql .= " AND tipo = :tipo";
            $params['tipo'] = $tipo;
        }

        $sql .= " ORDER BY tipo ASC, nome ASC";

        return $this->connDb->select($sql, $params);
    }

    /**
     * excluir
     * So deixa excluir categoria que pertence ao proprio usuario -- as
     * categorias padrao do sistema (usuario_id IS NULL) nunca podem ser
     * excluidas por um usuario comum.
     *
     * @param int $id
     * @param int $usuarioId
     * @return bool
     */
    public function excluir(int $id, int $usuarioId): bool
    {
        $sql = "DELETE FROM categorias WHERE id = :id AND usuario_id = :usuario_id";
        return $this->connDb->delete($sql, ['id' => $id, 'usuario_id' => $usuarioId]) > 0;
    }

    /**
     * buscarPorId
     *
     * @param int $id
     * @return array
     */
    public function buscarPorId(int $id): array
    {
        $sql = "SELECT * FROM categorias WHERE id = :id LIMIT 1";
        return $this->connDb->select($sql, ['id' => $id], 'one');
    }
}
