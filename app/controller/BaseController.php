<?php

namespace App\Controller;

use App\Library\Request;
use App\Library\Session;
use App\Library\Csrf;
use App\Model\UsuarioModel;

class BaseController
{
    public Request $request;

    /**
     * ACOES_SOMENTE_POST
     * Mapa central: Controller (nome curto da classe) => metodos que MUDAM
     * dado e por isso so podem ser executados via POST.
     *
     * Por que isso existe: o roteador (Routes.php) despacha pra
     * Controller/metodo olhando so o CAMINHO da URL -- ele nunca confere se
     * a requisicao veio como GET ou POST. E o verificarCsrf() so valida
     * token quando o metodo HTTP e POST/PUT/PATCH/DELETE; pra GET, ele
     * simplesmente nao faz nada. Ou seja: sem essa lista, qualquer acao
     * (excluir, bloquear, atualizar...) podia ser disparada tambem mandando
     * a mesma URL como GET, pulando o CSRF inteiro. Bastava alguem logado
     * clicar num link malicioso (ex: recebido por e-mail/mensagem) apontando
     * pra ela -- CSRF classico, mesmo com todos os formularios usando POST
     * certinho.
     *
     * Esse mapa fecha essa porta: mesmo que a URL seja acessada via GET,
     * verificarMetodoEscrita() barra a execucao se o metodo estiver aqui.
     *
     * De proposito FICAM DE FORA (nao viram GET-bloqueado):
     * - Projeto::aceitar e qualquer fluxo de convite/reset por e-mail --
     *   esses SAO acionados por um clique vindo de fora do sistema (o link
     *   no e-mail), entao nao da pra exigir um token CSRF de uma pagina que
     *   a pessoa nunca carregou. A protecao deles e outra: um token proprio,
     *   de uso unico e com validade, na propria URL.
     * - Login::logout -- o pior que um link malicioso conseguiria e deslogar
     *   a pessoa. Sem risco real, entao deixamos o link simples no menu.
     */
    protected const ACOES_SOMENTE_POST = [
        'Admin'                 => ['bloquear', 'ativar', 'promover', 'rebaixar', 'suporteAcessar', 'restaurarContatos', 'salvarTermo', 'ativarTermo', 'excluirContato', 'responder'],
        'Agenda'                => ['salvar', 'concluir', 'cancelar', 'excluir', 'excluirEmMassa', 'configurarLimpezaAutomatica'],
        'Cadastro'              => ['salvar'],
        'Cartao'                => ['salvar', 'atualizar', 'pagarFatura', 'deletar'],
        'Categoria'             => ['salvar', 'excluir'],
        'CompromissoRecorrente' => ['salvar', 'atualizar', 'excluir', 'gerarAgora'],
        'Conta'                 => ['salvar', 'atualizar', 'excluir', 'atualizarSaldoConta', 'definirContaDinheiro'],
        'Contato'               => ['enviar', 'limparHistorico', 'restaurarHistorico'],
        'Login'                 => ['login', 'enviarLinkReset', 'salvarNovaSenha'],
        'Orcamento'             => ['salvar', 'excluir'],
        'PlanoCompra'           => ['salvar', 'atualizar', 'excluir', 'restaurar', 'restaurarTodos', 'adicionarParcela', 'excluirParcela', 'concluir', 'cancelar'],
        'Projeto'               => ['salvar', 'removerColaborador', 'convidar', 'concluir', 'excluir', 'mensagem', 'sair'],
        'ProjetoRelatorio'      => ['salvar'],
        'Recorrencia'           => ['salvar', 'atualizar', 'alternar', 'excluir', 'gerarAgora'],
        'SolicitacaoSuporte'    => ['enviar', 'cancelar'],
        'SuporteChat'           => ['enviar', 'encerrar'],
        'Tarefa'                => ['criar', 'mover', 'atualizar', 'excluir'],
        'Termo'                 => ['aceitar'],
        'Transacao'             => ['lancar', 'atualizarCategoria', 'atualizar', 'excluir', 'excluirEmMassa', 'restaurar'],
        'Usuario'               => ['atualizar', 'alterarSenha', 'excluirConta'],
    ];

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
        $this->verificarMetodoEscrita();
        $this->verificarInatividade();
        $this->verificarAutenticacao();
    }

    /**
     * verificarMetodoEscrita
     * Barra a execucao de qualquer acao listada em ACOES_SOMENTE_POST se a
     * requisicao nao veio como POST -- ver o comentario da constante para
     * o motivo. Roda ANTES do verificarCsrf() nao ser suficiente sozinho:
     * aqui a checagem e pelo VERBO HTTP, nao pelo token.
     *
     * @return void
     */
    protected function verificarMetodoEscrita()
    {
        $nomeControllerCurto = substr(strrchr(static::class, '\\'), 1);
        $acoesRestritas = self::ACOES_SOMENTE_POST[$nomeControllerCurto] ?? [];

        if ($acoesRestritas === []) {
            return;
        }

        $metodo = $this->request->getMetodo();

        if (in_array($metodo, $acoesRestritas, true) && $this->request->getHttpMethod() !== 'POST') {
            http_response_code(405);
            Session::set('msgError', 'Essa acao so pode ser feita atraves do formulario correspondente.');
            header('Location: ' . BASEURL . 'Home');
            exit;
        }
    }

    /**
     * verificarInatividade
     * Desloga automaticamente quem ficou parado tempo demais (30 min por
     * padrao). Isso resolve o problema de verdade de "alguem pega o
     * computador depois de um tempo e acha a conta logada" -- fechar UMA
     * aba nunca desloga (todo navegador compartilha sessao entre abas, isso
     * e comportamento padrao da web, nao um bug), mas ficar parado tempo
     * demais, sim.
     *
     * @return void
     */
    protected function verificarInatividade()
    {
        $limiteSegundos = 30 * 60; // 30 minutos

        if (Session::get(SESSION_USER_KEY) && isset($_SESSION['ultima_atividade'])) {
            if ((time() - $_SESSION['ultima_atividade']) > $limiteSegundos) {
                unset($_SESSION[SESSION_USER_KEY]);
                Session::set('msgError', 'Sua sessao expirou por inatividade. Faca login novamente.');
            }
        }

        $_SESSION['ultima_atividade'] = time();
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
