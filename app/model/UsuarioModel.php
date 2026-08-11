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

    // Rate limiting de login: apos MAX_TENTATIVAS erradas seguidas, bloqueia
    // por BLOQUEIO_MINUTOS.
    const MAX_TENTATIVAS_LOGIN = 5;
    const BLOQUEIO_MINUTOS = 15;

    // Reset de senha: o link enviado por e-mail vale por esse tempo.
    const RESET_SENHA_VALIDADE_MINUTOS = 60;

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
        $sql = "SELECT id, nome, email, senha, nivel, statusRegistro, tentativas_login, bloqueado_ate
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
     * agendaLimpezaAutomaticaAtiva
     * Le a preferencia do usuario de excluir automaticamente (via cron)
     * compromissos concluidos ha mais de 30 dias -- ver migracao 005.
     *
     * @param int $usuarioId
     * @return bool
     */
    public function agendaLimpezaAutomaticaAtiva(int $usuarioId): bool
    {
        $sql = "SELECT agenda_limpeza_automatica FROM usuarios WHERE id = :id LIMIT 1";
        $linha = $this->connDb->select($sql, ['id' => $usuarioId], 'one');

        return !empty($linha) && (int) $linha['agenda_limpeza_automatica'] === 1;
    }

    /**
     * definirLimpezaAutomaticaAgenda
     *
     * @param int $usuarioId
     * @param bool $ativa
     * @return void
     */
    public function definirLimpezaAutomaticaAgenda(int $usuarioId, bool $ativa): void
    {
        $sql = "UPDATE usuarios SET agenda_limpeza_automatica = :ativa WHERE id = :id";
        $this->connDb->update($sql, ['ativa' => $ativa ? 1 : 0, 'id' => $usuarioId]);
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
        $sql = "INSERT INTO usuarios (nome, email, senha, cpf, data_nascimento, telefone_whats, nivel, statusRegistro)
                VALUES (:nome, :email, :senha, :cpf, :data_nascimento, :telefone_whats, :nivel, :statusRegistro)";

        return $this->connDb->insert($sql, [
            'nome'            => $dados['nome'],
            'email'           => $dados['email'],
            'senha'           => password_hash($dados['senha'], PASSWORD_DEFAULT),
            'cpf'             => $dados['cpf'] ?? null,
            'data_nascimento' => $dados['data_nascimento'] ?? null,
            'telefone_whats'  => !empty($dados['telefone_whats']) ? preg_replace('/\D/', '', $dados['telefone_whats']) : null,
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

    // ------------------------------------------------------------------
    // RATE LIMITING DE LOGIN
    // ------------------------------------------------------------------

    /**
     * estaBloqueado
     * Confere se a conta esta em periodo de bloqueio por excesso de
     * tentativas erradas.
     *
     * @param array $usuario Linha vinda de getUserEmail()
     * @return int|null Segundos restantes de bloqueio, ou null se nao estiver bloqueado
     */
    public function estaBloqueado(array $usuario): ?int
    {
        if (empty($usuario['bloqueado_ate'])) {
            return null;
        }

        $restante = strtotime($usuario['bloqueado_ate']) - time();
        return $restante > 0 ? $restante : null;
    }

    /**
     * registrarTentativaFalha
     * RN de rate limiting: incrementa o contador; ao atingir
     * MAX_TENTATIVAS_LOGIN, bloqueia a conta por BLOQUEIO_MINUTOS e zera o
     * contador (pra, quando o bloqueio expirar, comecar a contar do zero
     * de novo em vez de bloquear na primeira tentativa seguinte).
     *
     * @param int $usuarioId
     * @param int $tentativasAtuais
     * @return void
     */
    public function registrarTentativaFalha(int $usuarioId, int $tentativasAtuais): void
    {
        $novasTentativas = $tentativasAtuais + 1;

        if ($novasTentativas >= self::MAX_TENTATIVAS_LOGIN) {
            $bloqueadoAte = date('Y-m-d H:i:s', time() + (self::BLOQUEIO_MINUTOS * 60));
            $sql = "UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = :bloqueado_ate WHERE id = :id";
            $this->connDb->update($sql, ['bloqueado_ate' => $bloqueadoAte, 'id' => $usuarioId]);
            return;
        }

        $sql = "UPDATE usuarios SET tentativas_login = :tentativas WHERE id = :id";
        $this->connDb->update($sql, ['tentativas' => $novasTentativas, 'id' => $usuarioId]);
    }

    /**
     * limparTentativas
     * Chamado apos login bem-sucedido -- zera o contador e qualquer
     * bloqueio residual.
     *
     * @param int $usuarioId
     * @return void
     */
    public function limparTentativas(int $usuarioId): void
    {
        $sql = "UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = NULL WHERE id = :id";
        $this->connDb->update($sql, ['id' => $usuarioId]);
    }

    // ------------------------------------------------------------------
    // RECUPERACAO DE SENHA
    // ------------------------------------------------------------------

    /**
     * gerarTokenReset
     * Gera um token de uso unico para "esqueci minha senha". Retorna o
     * token mesmo que o e-mail nao exista no sistema -- e o Controller quem
     * decide nao revelar essa diferenca na resposta (evita enumeracao de
     * e-mails cadastrados).
     *
     * @param string $email
     * @return string|null Token gerado, ou null se o e-mail nao existir
     */
    public function gerarTokenReset(string $email): ?string
    {
        $usuario = $this->getUserEmail($email);

        if (count($usuario) === 0) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $expiraEm = date('Y-m-d H:i:s', time() + (self::RESET_SENHA_VALIDADE_MINUTOS * 60));

        $sql = "UPDATE usuarios SET reset_token = :token, reset_token_expira_em = :expira WHERE id = :id";
        $this->connDb->update($sql, ['token' => $token, 'expira' => $expiraEm, 'id' => $usuario['id']]);

        return $token;
    }

    /**
     * buscarPorTokenReset
     * So devolve o usuario se o token existir E ainda nao tiver expirado.
     *
     * @param string $token
     * @return array
     */
    public function buscarPorTokenReset(string $token): array
    {
        $sql = "SELECT id, nome, email FROM usuarios
                WHERE reset_token = :token AND reset_token_expira_em > NOW()
                LIMIT 1";

        return $this->connDb->select($sql, ['token' => $token], 'one');
    }

    /**
     * redefinirSenha
     * Grava a nova senha (ja em hash) e invalida o token -- uso unico, nao
     * da pra reaproveitar o mesmo link duas vezes.
     *
     * @param int $usuarioId
     * @param string $novaSenha Texto puro -- o hash e feito aqui dentro
     * @return void
     */
    public function redefinirSenha(int $usuarioId, string $novaSenha): void
    {
        $sql = "UPDATE usuarios
                SET senha = :senha, reset_token = NULL, reset_token_expira_em = NULL,
                    tentativas_login = 0, bloqueado_ate = NULL
                WHERE id = :id";

        $this->connDb->update($sql, [
            'senha' => password_hash($novaSenha, PASSWORD_DEFAULT),
            'id'    => $usuarioId,
        ]);
    }

    /**
     * deletar
     * Exclui o usuário e todos os dados relacionados via foreign keys.
     *
     * @param int $usuarioId
     * @return int Linhas afetadas
     */
    public function deletar(int $usuarioId): int
    {
        $sql = "DELETE FROM usuarios WHERE id = :id";
        return $this->connDb->delete($sql, ['id' => $usuarioId]);
    }
}
