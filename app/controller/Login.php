<?php
namespace App\Controller;

use App\Library\Mailer;
use App\Library\Session;

class Login extends BaseController
{
    protected $model;

    /**
     * construct
     */
    public function __construct()
    {
        parent::__construct();                          // Chama o método construtor da classe BaseController
        $this->model = $this->model('Usuario');
        $this->helper("crud");                          // Carregar os Helpers
    }

    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        return $this->view("login");
    }

    /**
     * login
     * RN de rate limiting: apos UsuarioModel::MAX_TENTATIVAS_LOGIN senhas
     * erradas seguidas, a conta fica bloqueada por
     * UsuarioModel::BLOQUEIO_MINUTOS -- protege contra forca bruta.
     *
     * @return void
     */
    public function login()
    {
        $aUser = $this->model->getUserEmail(trim($_POST['email']));

        if (count($aUser) === 0) {
            $_SESSION["msgError"] = "Login ou Senha inválidos.";
            return header("location: /Login");
        }

        // Confere bloqueio ANTES de checar a senha -- nao adianta nada
        // bloquear so depois de validar (isso ja vazaria timing/info).
        $segundosBloqueado = $this->model->estaBloqueado($aUser);
        if ($segundosBloqueado !== null) {
            $minutos = (int) ceil($segundosBloqueado / 60);
            Session::set('msgError', "Conta temporariamente bloqueada por excesso de tentativas. Tente novamente em {$minutos} minuto(s).");
            return header("Location: /Login");
        }

        // validar a senha
        if (!password_verify(trim($_POST["password"]), trim($aUser['senha']))) {
            $this->model->registrarTentativaFalha((int) $aUser['id'], (int) $aUser['tentativas_login']);

            $tentativasRestantes = \App\Model\UsuarioModel::MAX_TENTATIVAS_LOGIN - ((int) $aUser['tentativas_login'] + 1);

            if ($tentativasRestantes > 0) {
                $_SESSION["msgError"] = "Login ou Senha inválidos. Restam {$tentativasRestantes} tentativa(s) antes do bloqueio temporário.";
            } else {
                $_SESSION["msgError"] = "Login ou Senha inválidos. Conta bloqueada por " . \App\Model\UsuarioModel::BLOQUEIO_MINUTOS . " minutos por excesso de tentativas.";
            }

            return header("Location: /Login");
        }

        // validar o status do usuário
        if ($aUser['statusRegistro'] == 2) {
            $_SESSION["msgError"] = 'Usuário Inativo, não será possível prosseguir !';
            return header("Location: /Login");
        }

        // Login certo: zera o contador de tentativas erradas.
        $this->model->limparTentativas((int) $aUser['id']);

        //  Criar flag's de usuário logado no sistema
        $_SESSION["userLogado"] = [
                                        "id"    => $aUser['id'],
                                        "nome"  => $aUser['nome'],
                                        "email" => $aUser['email'],
                                        "nivel" => $aUser['nivel'],
                                        "senha" => $aUser['senha']
                                    ];

        $termoModel = $this->model('Termo');
        $aceitouTodos = $termoModel->usuarioAceitouTodosAtivos((int) $aUser['id']);

        if (!$aceitouTodos) {
            return header("Location: /Termo");
        }

        // Direcionar o usuário para a Home (dashboard) apos login
        return header("Location: /Home");
        //
    }

    /**
     * logout
     *
     * @return void
     */
    public function logout()
    {
        unset($_SESSION['userLogado']);
        return header("Location: /Home");
    }

    /**
     * esqueciSenha
     * Formulario onde o usuario informa o e-mail pra receber o link de reset.
     *
     * @return void
     */
    public function esqueciSenha()
    {
        return $this->view('esqueciSenha');
    }

    /**
     * enviarLinkReset
     * RN de seguranca: a resposta e SEMPRE a mesma mensagem generica, exista
     * ou nao esse e-mail no sistema -- isso evita "enumeracao de e-mail"
     * (alguem descobrir quais e-mails tem conta so testando essa tela).
     *
     * @return void
     */
    public function enviarLinkReset()
    {
        $email = trim($_POST['email'] ?? '');

        $mensagemGenerica = 'Se esse e-mail estiver cadastrado, enviamos um link de recuperação para ele.';

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::set('msgSucesso', $mensagemGenerica);
            return header('Location: /Login/esqueciSenha');
        }

        $token = $this->model->gerarTokenReset($email);

        if ($token !== null) {
            $usuario = $this->model->getUserEmail($email);
            $link = BASEURL . "Login/redefinirSenha/{$token}";

            $corpoHtml = "
                <p>Ola, " . htmlspecialchars($usuario['nome']) . "!</p>
                <p>Recebemos um pedido pra redefinir a senha da sua conta no Projeto GP.</p>
                <p><a href=\"{$link}\">Clique aqui para criar uma nova senha</a></p>
                <p>Esse link vale por " . \App\Model\UsuarioModel::RESET_SENHA_VALIDADE_MINUTOS . " minutos. Se voce nao pediu isso, pode ignorar este e-mail.</p>
            ";

            Mailer::enviar($email, $usuario['nome'], 'Recuperação de senha -- Projeto GP', $corpoHtml);
        }

        // Mesma mensagem, tenha o e-mail sido encontrado ou nao.
        Session::set('msgSucesso', $mensagemGenerica);
        return header('Location: /Login');
    }

    /**
     * redefinirSenhaForm
     * URL: /Login/redefinirSenha/{token}
     *
     * @return void
     */
    public function redefinirSenhaForm()
    {
        $token = (string) $this->request->getAction();
        $usuario = $this->model->buscarPorTokenReset($token);

        if (count($usuario) === 0) {
            Session::set('msgError', 'Link invalido ou expirado. Peca um novo link.');
            return header('Location: /Login/esqueciSenha');
        }

        return $this->view('redefinirSenha', ['token' => $token]);
    }

    /**
     * salvarNovaSenha
     *
     * @return void
     */
    public function salvarNovaSenha()
    {
        $token = trim($_POST['token'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';

        $usuario = $this->model->buscarPorTokenReset($token);

        if (count($usuario) === 0) {
            Session::set('msgError', 'Link invalido ou expirado. Peca um novo link.');
            return header('Location: /Login/esqueciSenha');
        }

        if (strlen($senha) < 6) {
            Session::set('msgError', 'A senha precisa ter pelo menos 6 caracteres.');
            return header("Location: /Login/redefinirSenha/{$token}");
        }

        if ($senha !== $confirmarSenha) {
            Session::set('msgError', 'As senhas informadas nao conferem.');
            return header("Location: /Login/redefinirSenha/{$token}");
        }

        $this->model->redefinirSenha((int) $usuario['id'], $senha);

        Session::set('msgSucesso', 'Senha redefinida com sucesso! Faca login com a nova senha.');
        return header('Location: /Login');
    }

    /**
     * criaSuperUser
     *
     * @return void
     */
    public function criaSuperUser()
    {
        $dados = [
            "nome"              => "Israel Ferrera",
            "email"             => "if739871@gmail.com",
            "senha"             => "if739871",
            "nivel"             => 1,
            "statusRegistro"    => 1
        ];

        $aSuperUser = $this->model->getUserEmail($dados['email']);

        if (count($aSuperUser) > 0) {
            $_SESSION["msgError"] = "Login já existe.";
            return header("location: /Login");
        } else {
            if ($this->model->insert($dados)) {
                $_SESSION["msgSucesso"] = "Login criado com sucesso.";
                return header("location: /Login");
            } else {
                return header("location: /Login");
            }
        }
    }
}
