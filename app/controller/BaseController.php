<?php

namespace App\Controller;

use App\Library\Request;
use App\Library\Session;
use App\Library\Csrf;
use App\Model\UsuarioModel;

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

        $usuario = Session::get(SESSION_USER_KEY);
        $controllerAtual = $this->request->getController();
        $metodoAtual = $this->request->getMetodo();

        if (is_array($usuario)) {
            if ($controllerAtual === 'Termo') {
                return;
            }

            if ($controllerAtual === 'Home' && $metodoAtual === 'termoPolitica') {
                return;
            }

            $isAdmin = (int) ($usuario['nivel'] ?? 0) === UsuarioModel::NIVEL_ADMIN;

            if (!$isAdmin && !$this->model('Termo')->usuarioAceitouTodosAtivos((int) $usuario['id'])) {
                header("Location: " . BASEURL . "Termo");
                exit;
            }

            if ($isAdmin && $controllerAtual !== 'Admin' && !$this->model('Termo')->usuarioAceitouTodosAtivos((int) $usuario['id'])) {
                header("Location: " . BASEURL . "Termo");
                exit;
            }
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
     * temAcessoSuporteAtivo
     * Admin NAO tem bypass automatico para recursos privados de outros
     * usuarios (projeto, conta, cartao, compromisso, plano_compra). O unico
     * jeito de acessar e passando por Admin::suporteAcessar(), que exige
     * justificativa, grava no log_acesso_suporte permanentemente, e concede
     * uma janela curta guardada na sessao -- e essa janela que este metodo
     * confere. Sem essa concessao explicita, o acesso e negado como para
     * qualquer outro usuario que nao seja dono do recurso.
     *
     * @param string $tipoRecurso 'projeto'|'conta'|'cartao'|'compromisso'|'plano_compra'
     * @param int $recursoId
     * @return bool
     */
    protected function temAcessoSuporteAtivo(string $tipoRecurso, int $recursoId): bool
    {
        $concessao = Session::get('acessoSuporte');

        if (!is_array($concessao)) {
            return false;
        }

        if (($concessao['tipo_recurso'] ?? null) !== $tipoRecurso || (int) ($concessao['recurso_id'] ?? 0) !== $recursoId) {
            return false;
        }

        if (strtotime($concessao['expira_em']) < time()) {
            Session::destroy('acessoSuporte');
            return false;
        }

        return true;
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
