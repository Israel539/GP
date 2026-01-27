<?php
namespace App\Controller;

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
     *
     * @return void
     */
    public function login()
    {
        $aUser = $this->model->getUserEmail(trim($_POST['email']));

        if (count($aUser) > 0) {

            // validar a senha            
            if (!password_verify(trim($_POST["password"]), trim($aUser['senha']))) {
                $_SESSION["msgError"] = 'Login ou Senha inválidos.';
                return header("Location: /Login");            }
            
            // validar o status do usuário            
            if ($aUser['statusRegistro'] == 2 ) {
                $_SESSION["msgError"] = 'Usuário Inativo, não será possível prosseguir !';
                return header("Location: /Login");
            }

            //  Criar flag's de usuário logado no sistema           
            $_SESSION["userLogado"] = [
                                            "id"    => $aUser['id'],
                                            "nome"  => $aUser['nome'],
                                            "email" => $aUser['email'],
                                            "nivel" => $aUser['nivel'],
                                            "senha" => $aUser['senha']
                                        ];
            
            // Direcionar o usuário para página home da área administrativa
            return header("Location: /Sistema");
            //

        } else {
            $_SESSION["msgError"] = "Login ou Senha inválidos.";
            return header("location: /Login");
        }
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