<?php

namespace App\Controller;

use App\Library\Request;
use App\Library\Session;
use App\Library\Csrf;

class BaseController
{
    public $request;

    public function __construct()
    {
        $this->request = new Request();

        // Helpers universais: quase toda view usa baseUrl() (utilits.php) e/ou
        // mensagens() (crud.php). Carregar aqui, uma vez, para todo Controller,
        // evita repetir o bug de uma view chamar uma funcao de helper que o
        // Controller especifico esqueceu de carregar (ja aconteceu com
        // Login/Cadastro chamando baseUrl() sem terem dado helper("utilits")).
        $this->helper(['utilits', 'crud']);

        // Impede que o navegador guarde em cache paginas internas do sistema
        // (ex: apertar "voltar" depois do logout nao deveria reexibir a tela)
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");

        $this->verificarCsrf();
        $this->verificarAutenticacao();
    }

    /**
     * verificarCsrf
     * Bloqueia qualquer requisicao que muda estado (POST/PUT/PATCH/DELETE)
     * sem um token CSRF valido.
     *
     * @return void
     */
    protected function verificarCsrf()
    {
        if (!CSRF_PROTECTION || Csrf::isExcluded()) {
            return;
        }

        $httpMethod = $this->request->getHttpMethod();

        if (in_array($httpMethod, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $token = $_POST[CSRF_TOKEN_NAME] ?? null;

            if (!Csrf::validate($token)) {
                http_response_code(419);
                Session::set('msgError', 'Sessao expirada ou token invalido. Tente novamente.');
                header("Location: " . BASEURL . "Login");
                exit;
            }
        }
    }

    /**
     * verificarAutenticacao
     * Controllers fora de CONTROLLERS_PUBLICOS exigem usuario logado.
     *
     * @return void
     */
    protected function verificarAutenticacao()
    {
        $controllerAtual = $this->request->getController();

        if (in_array($controllerAtual, CONTROLLERS_PUBLICOS)) {
            return;
        }

        if (!Session::get(SESSION_USER_KEY)) {
            Session::set('msgError', 'Para acessar o sistema, faca login primeiro.');
            header("Location: " . BASEURL . "Login");
            exit;
        }
    }

    /**
     * usuarioLogado
     * Atalho para pegar os dados do usuario autenticado na sessao.
     *
     * @return array|false
     */
    public function usuarioLogado()
    {
        return Session::get(SESSION_USER_KEY);
    }

    /**
     * model
     *
     * @param string $nomeModel
     * @return object
     */
    public function model(string $nomeModel): object
    {
        $nomeModel .= "Model";
        $fullClassName = "App\\Model\\" . $nomeModel;

        if (class_exists($fullClassName)) {
            return new $fullClassName();
        } else {
            return (object) null;
        }
    }

    /**
     * helper
     *
     * @param string|array $nomeHelper
     * @return void
     */
    public function helper(string|array $nomeHelper)
    {
        if (gettype($nomeHelper) == "string") {
            $nomeHelper = [$nomeHelper];
        }

        foreach ($nomeHelper as $helper) {
            $path = __DIR__ . "/../helper/" . $helper . ".php";
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    /**
     * view
     *
     * @param string $view
     * @param array $data
     * @return void
     */
    public function view(string $view, array $data = [])
    {
        $data['action'] = $this->request->getAction();

        $viewPath  = __DIR__ . "/../view/" . $view . ".php";
        $errorPath = __DIR__ . "/../view/comuns/erros.php";

        if (file_exists($viewPath)) {
            extract($data);
            require_once $viewPath;
        } else {
            if (file_exists($errorPath)) {
                require_once $errorPath;
            } else {
                echo "Erro critico: A view solicitada ('$view') e a pagina de erro nao foram encontradas.";
            }
        }
    }
}
