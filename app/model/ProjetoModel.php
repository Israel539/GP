<?php

namespace App\Model;

class ProjetoModel extends BaseModel
{
    const PAPEL_DONO        = 'dono';
    const PAPEL_COLABORADOR = 'colaborador';

    const STATUS_PLANEJAMENTO = 'planejamento';
    const STATUS_ANDAMENTO    = 'em_andamento';
    const STATUS_CONCLUIDO    = 'concluido';
    const STATUS_CANCELADO    = 'cancelado';

    protected $validationRules = [
        'nome' => ['rules' => 'required|min:3|max:150', 'label' => 'Nome do projeto'],
    ];

    /**
     * criar
     * Cria o projeto e já vincula o criador como 'dono' em projeto_usuarios --
     * as duas operações precisam acontecer juntas, então usamos transação:
     * se o vínculo falhar, o projeto órfão (sem dono) não fica gravado.
     *
     * @param array $dados ('nome', 'descricao'?, 'data_inicio'?, 'data_entrega'?)
     * @param int $donoId
     * @return int Id do projeto criado, ou 0 em caso de falha
     */
    public function criar(array $dados, int $donoId): int
    {
        $conn = (new \App\Library\Database())->conecta();

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare(
                "INSERT INTO projetos (dono_id, nome, descricao, status, data_inicio, data_entrega)
                 VALUES (:dono_id, :nome, :descricao, :status, :data_inicio, :data_entrega)"
            );
            $stmt->execute([
                'dono_id'      => $donoId,
                'nome'         => $dados['nome'],
                'descricao'    => $dados['descricao'] ?? null,
                'status'       => self::STATUS_PLANEJAMENTO,
                'data_inicio'  => $dados['data_inicio'] ?? null,
                'data_entrega' => $dados['data_entrega'] ?? null,
            ]);

            $projetoId = (int) $conn->lastInsertId();

            $stmt = $conn->prepare(
                "INSERT INTO projeto_usuarios (projeto_id, usuario_id, papel)
                 VALUES (:projeto_id, :usuario_id, :papel)"
            );
            $stmt->execute([
                'projeto_id' => $projetoId,
                'usuario_id' => $donoId,
                'papel'      => self::PAPEL_DONO,
            ]);

            $conn->commit();
            return $projetoId;

        } catch (\Exception $ex) {
            $conn->rollBack();
            return 0;
        }
    }

    /**
     * contarTodos
     * Estatistica agregada para o painel do Admin -- so o numero, nunca a
     * lista com nomes/donos (isso exigiria o fluxo de Admin::suporteAcessar()).
     *
     * @return int
     */
    public function contarTodos(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM projetos";
        $linha = $this->connDb->select($sql, [], 'one');
        return (int) ($linha['total'] ?? 0);
    }

    /**
     * buscarPorId
     *
     * @param int $id
     * @return array
     */
    public function buscarPorId(int $id): array
    {
        $sql = "SELECT * FROM projetos WHERE id = :id LIMIT 1";
        return $this->connDb->select($sql, ['id' => $id], 'one');
    }

    /**
     * listarPorUsuario
     * Lista os projetos que o usuário participa (dono OU colaborador).
     * NAO existe mais bypass de admin aqui -- ver Admin::suporteAcessar()
     * para o fluxo auditado de acesso pontual a um projeto especifico.
     *
     * @param int $usuarioId
     * @return array
     */
    public function listarPorUsuario(int $usuarioId): array
    {
        $sql = "SELECT p.*, pu.papel
                FROM projetos p
                INNER JOIN projeto_usuarios pu ON pu.projeto_id = p.id
                WHERE pu.usuario_id = :usuario_id
                ORDER BY p.atualizado_em DESC, p.id DESC";

        return $this->connDb->select($sql, ['usuario_id' => $usuarioId]);
    }

    /**
     * usuarioParticipa
     * Checagem de autorização: usuário só pode ver/mexer no projeto se
     * estiver em projeto_usuarios (ou for admin -- isso o Controller decide
     * antes de chamar, olhando UsuarioModel::isAdmin()).
     *
     * @param int $projetoId
     * @param int $usuarioId
     * @return bool
     */
    public function usuarioParticipa(int $projetoId, int $usuarioId): bool
    {
        $sql = "SELECT id FROM projeto_usuarios
                WHERE projeto_id = :projeto_id AND usuario_id = :usuario_id
                LIMIT 1";

        $linha = $this->connDb->select($sql, [
            'projeto_id' => $projetoId,
            'usuario_id' => $usuarioId,
        ], 'one');

        return count($linha) > 0;
    }

    /**
     * listarColaboradores
     *
     * @param int $projetoId
     * @return array
     */
    public function listarColaboradores(int $projetoId): array
    {
        $sql = "SELECT u.id, u.nome, u.email, pu.papel, pu.entrou_em
                FROM projeto_usuarios pu
                INNER JOIN usuarios u ON u.id = pu.usuario_id
                WHERE pu.projeto_id = :projeto_id
                ORDER BY pu.papel ASC, u.nome ASC";

        return $this->connDb->select($sql, ['projeto_id' => $projetoId]);
    }

    /**
     * usuarioEhDono
     * Retorna true se o usuario é dono do projeto.
     *
     * @param int $projetoId
     * @param int $usuarioId
     * @return bool
     */
    public function usuarioEhDono(int $projetoId, int $usuarioId): bool
    {
        $sql = "SELECT id FROM projeto_usuarios
                WHERE projeto_id = :projeto_id
                  AND usuario_id = :usuario_id
                  AND papel = :papel
                LIMIT 1";

        $linha = $this->connDb->select($sql, [
            'projeto_id' => $projetoId,
            'usuario_id' => $usuarioId,
            'papel'      => self::PAPEL_DONO,
        ], 'one');

        return count($linha) > 0;
    }

    /**
     * removerParticipante
     * Remove um usuario do projeto.
     *
     * @param int $projetoId
     * @param int $usuarioId
     * @return bool
     */
    public function removerParticipante(int $projetoId, int $usuarioId): bool
    {
        $sql = "DELETE FROM projeto_usuarios
                WHERE projeto_id = :projeto_id
                  AND usuario_id = :usuario_id";

        return $this->connDb->delete($sql, [
            'projeto_id' => $projetoId,
            'usuario_id' => $usuarioId,
        ]) > 0;
    }

    /**
     * convidar
     * Gera um convite pendente por e-mail. Quem convida precisa já
     * participar do projeto -- validar isso no Controller antes de chamar.
     *
     * @param int $projetoId
     * @param int $convidadoPor
     * @param string $emailConvidado
     * @return string Token do convite (usado no link enviado por e-mail)
     */
    public function convidar(int $projetoId, int $convidadoPor, string $emailConvidado): string
    {
        $token = bin2hex(random_bytes(32));

        $sql = "INSERT INTO projeto_convites (projeto_id, convidado_por, email_convidado, status, token)
                VALUES (:projeto_id, :convidado_por, :email, 'pendente', :token)";

        $this->connDb->insert($sql, [
            'projeto_id'    => $projetoId,
            'convidado_por' => $convidadoPor,
            'email'         => $emailConvidado,
            'token'         => $token,
        ]);

        return $token;
    }

    /**
     * aceitarConvite
     * Chamado quando o convidado clica no link recebido e já está logado
     * (ou acabou de se cadastrar). Marca o convite como aceito e cria o
     * vínculo em projeto_usuarios.
     *
     * @param string $token
     * @param int $usuarioId
     * @return string 'ok' | 'invalido' | 'ja_participa'
     */
    public function aceitarConvite(string $token, int $usuarioId): string
    {
        $sql = "SELECT * FROM projeto_convites
                WHERE token = :token AND status = 'pendente'
                LIMIT 1";

        $convite = $this->connDb->select($sql, ['token' => $token], 'one');

        if (count($convite) === 0) {
            return 'invalido'; // token inválido, já usado ou expirado
        }

        if ($this->usuarioParticipa((int) $convite['projeto_id'], $usuarioId)) {
            // Já é colaborador (ou dono) -- evita duplicar (mesmo espírito
            // da RN10). Marca o convite como aceito mesmo assim, pra não
            // ficar um convite pendente "morto" pra sempre.
            $this->connDb->update(
                "UPDATE projeto_convites SET status = 'aceito' WHERE id = :id",
                ['id' => $convite['id']]
            );
            return 'ja_participa';
        }

        $sqlInsert = "INSERT INTO projeto_usuarios (projeto_id, usuario_id, papel)
                      VALUES (:projeto_id, :usuario_id, 'colaborador')";
        $this->connDb->insert($sqlInsert, [
            'projeto_id' => $convite['projeto_id'],
            'usuario_id' => $usuarioId,
        ]);

        $sqlUpdate = "UPDATE projeto_convites SET status = 'aceito' WHERE id = :id";
        $this->connDb->update($sqlUpdate, ['id' => $convite['id']]);

        return 'ok';
    }

    /**
     * concluir
     * RN05: só marca como 'concluido' se não houver tarefa pendente
     * ('a_fazer' ou 'em_andamento') vinculada ao projeto.
     *
     * @param int $projetoId
     * @return bool true se concluiu, false se bloqueado pela RN05
     */
    public function concluir(int $projetoId): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM tarefas
                WHERE projeto_id = :projeto_id AND status IN ('a_fazer', 'em_andamento')";

        $resultado = $this->connDb->select($sql, ['projeto_id' => $projetoId], 'one');

        if ((int) $resultado['total'] > 0) {
            return false; // RN05: ainda tem tarefa em aberto, bloqueia
        }

        $sqlUpdate = "UPDATE projetos SET status = :status WHERE id = :id";
        $this->connDb->update($sqlUpdate, [
            'status' => self::STATUS_CONCLUIDO,
            'id'     => $projetoId,
        ]);

        return true;
    }
}
