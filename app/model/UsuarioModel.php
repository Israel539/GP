<?php

namespace App\Model;

class UsuarioModel extends BaseModel
{
    // Níveis de acesso (RN de autorização, não está no documento original
    // mas segue o mesmo padrão que o Login.php/criaSuperUser já usam)
    const NIVEL_ADMIN  = 1;
    const NIVEL_COMUM  = 2;

    const STATUS_ATIVO   = 1;
    const STATUS_INATIVO = 2;

    protected $validationRules = [
        'nome'  => ['rules' => 'required|min:3|max:120', 'label' => 'Nome'],
        'email' => ['rules' => 'required|email|max:150', 'label' => 'E-mail'],
        'senha' => ['rules' => 'required|min:6',         'label' => 'Senha'],
    ];

    /**
     * getUserEmail
     * Usado pelo Login::login() para autenticar. Retorna UMA linha (array
     * associativo) ou [] se não encontrar.
     *
     * @param string $email
     * @return array
     */
    public function getUserEmail(string $email): array
    {
        $sql = "SELECT id, nome, email, senha, nivel, statusRegistro
                FROM usuarios
                WHERE email = :email
                LIMIT 1";

        return $this->connDb->select($sql, ['email' => $email], 'one');
    }

    /**
     * buscarPorId
     *
     * @param int $id
     * @return array
     */
    public function buscarPorId(int $id): array
    {
        $sql = "SELECT id, nome, email, cpf, data_nascimento, telefone_whats,
                       foto, nivel, statusRegistro, criado_em
                FROM usuarios
                WHERE id = :id
                LIMIT 1";

        return $this->connDb->select($sql, ['id' => $id], 'one');
    }

    /**
     * insert
     * Cadastra um novo usuário. Sempre entra como NIVEL_COMUM -- ninguém vira
     * admin via formulário público, só via alterarNivel() por outro admin
     * (ou o bootstrap manual documentado no schema).
     *
     * @param array $dados ('nome', 'email', 'senha' em texto puro, 'cpf'?, 'data_nascimento'?)
     * @return int Id gerado, ou 0 em caso de falha
     */
    public function insert(array $dados): int
    {
        $sql = "INSERT INTO usuarios (nome, email, senha, cpf, data_nascimento, nivel, statusRegistro)
                VALUES (:nome, :email, :senha, :cpf, :data_nascimento, :nivel, :statusRegistro)";

        return $this->connDb->insert($sql, [
            'nome'            => $dados['nome'],
            'email'           => $dados['email'],
            'senha'           => password_hash($dados['senha'], PASSWORD_DEFAULT),
            'cpf'             => $dados['cpf'] ?? null,
            'data_nascimento' => $dados['data_nascimento'] ?? null,
            'nivel'           => $dados['nivel'] ?? self::NIVEL_COMUM,
            'statusRegistro'  => self::STATUS_ATIVO,
        ]);
    }

    /**
     * emailJaExiste
     * Útil no Cadastro, antes de tentar o insert.
     *
     * @param string $email
     * @return bool
     */
    public function emailJaExiste(string $email): bool
    {
        return count($this->getUserEmail($email)) > 0;
    }

    /**
     * isAdmin
     * Atalho para checar o array da sessão (userLogado) em Controllers/Views.
     * Ex: $this->model('Usuario')->isAdmin($this->usuarioLogado())
     *
     * @param array|false $usuarioSessao
     * @return bool
     */
    public function isAdmin($usuarioSessao): bool
    {
        return is_array($usuarioSessao) && (int) ($usuarioSessao['nivel'] ?? 0) === self::NIVEL_ADMIN;
    }

    /**
     * listarTodos
     * Só deve ser chamado por um Controller que já validou que quem pediu é
     * admin -- o Model não sabe quem está logado, só executa a query.
     *
     * @return array
     */
    public function listarTodos(): array
    {
        $sql = "SELECT id, nome, email, nivel, statusRegistro, criado_em
                FROM usuarios
                ORDER BY nome ASC";

        return $this->connDb->select($sql);
    }

    /**
     * alterarStatus
     * Admin ativa/bloqueia um usuário (bane sem precisar apagar o registro).
     *
     * @param int $id
     * @param int $status self::STATUS_ATIVO ou self::STATUS_INATIVO
     * @return int Linhas afetadas
     */
    public function alterarStatus(int $id, int $status): int
    {
        $sql = "UPDATE usuarios SET statusRegistro = :status WHERE id = :id";
        return $this->connDb->update($sql, ['status' => $status, 'id' => $id]);
    }

    /**
     * alterarNivel
     * Promove/rebaixa um usuário. Único jeito de criar outro admin depois
     * do bootstrap inicial.
     *
     * @param int $id
     * @param int $nivel self::NIVEL_ADMIN ou self::NIVEL_COMUM
     * @return int Linhas afetadas
     */
    public function alterarNivel(int $id, int $nivel): int
    {
        $sql = "UPDATE usuarios SET nivel = :nivel WHERE id = :id";
        return $this->connDb->update($sql, ['nivel' => $nivel, 'id' => $id]);
    }
}
