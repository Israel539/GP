<?php

namespace App\Controller;

use App\Library\Mailer;
use App\Library\Session;
use App\Model\UsuarioModel;

class Admin extends BaseController
{
    protected $usuarioModel;
    protected $projetoModel;
    protected $termoModel;

    public function __construct()
    {
        parent::__construct();     // ja garante que tem alguem logado (Admin nao esta em CONTROLLERS_PUBLICOS)
        $this->usuarioModel = $this->model('Usuario');
        $this->projetoModel = $this->model('Projeto');
        $this->termoModel = $this->model('Termo');
        $this->helper("crud");

        $this->verificarAdmin();
    }

    /**
     * verificarAdmin
     * Camada extra sobre o BaseController::verificarAutenticacao(): aqui nao
     * basta estar logado, precisa ser nivel = admin. Quem nao for, e
     * devolvido para a Home com aviso -- nunca ve nem o layout do painel.
     *
     * @return void
     */
    protected function verificarAdmin()
    {
        if (!$this->usuarioModel->isAdmin($this->usuarioLogado())) {
            http_response_code(403);
            Session::set('msgError', 'Acesso restrito a administradores.');
            header("Location: /Home");
            exit;
        }
    }

    /**
     * index
     * Painel resumo.
     *
     * @return void
     */
    public function index()
    {
        $usuarios = $this->usuarioModel->listarTodos();
        $projetos = $this->projetoModel->listarPorUsuario(0, true);

        return $this->view("adminIndex", [
            'totalUsuarios' => count($usuarios),
            'totalProjetos' => count($projetos),
        ]);
    }

    /**
     * usuarios
     * Lista todos os usuarios do sistema para gestao (bloquear/promover).
     *
     * @return void
     */
    public function usuarios()
    {
        $usuarios = $this->usuarioModel->listarTodos();

        return $this->view("adminUsuarios", [
            'usuarios'   => $usuarios,
            'meuId'      => (int) $this->usuarioLogado()['id'],
            'NIVEL_ADMIN' => UsuarioModel::NIVEL_ADMIN,
        ]);
    }

    /**
     * bloquear
     * URL: /Admin/bloquear/{id}
     *
     * @return void
     */
    public function bloquear()
    {
        $id = (int) $this->request->getAction();

        if ($id === (int) $this->usuarioLogado()['id']) {
            Session::set('msgError', 'Voce nao pode bloquear a si mesmo.');
            return header("Location: /Admin/usuarios");
        }

        $this->usuarioModel->alterarStatus($id, UsuarioModel::STATUS_INATIVO);
        Session::set('msgSucesso', 'Usuario bloqueado.');
        return header("Location: /Admin/usuarios");
    }

    /**
     * ativar
     * URL: /Admin/ativar/{id}
     *
     * @return void
     */
    public function ativar()
    {
        $id = (int) $this->request->getAction();

        $this->usuarioModel->alterarStatus($id, UsuarioModel::STATUS_ATIVO);
        Session::set('msgSucesso', 'Usuario ativado.');
        return header("Location: /Admin/usuarios");
    }

    /**
     * promover
     * URL: /Admin/promover/{id} -- vira admin.
     *
     * @return void
     */
    public function promover()
    {
        $id = (int) $this->request->getAction();

        $this->usuarioModel->alterarNivel($id, UsuarioModel::NIVEL_ADMIN);
        Session::set('msgSucesso', 'Usuario promovido a administrador.');
        return header("Location: /Admin/usuarios");
    }

    /**
     * rebaixar
     * URL: /Admin/rebaixar/{id} -- volta a usuario comum.
     *
     * @return void
     */
    public function rebaixar()
    {
        $id = (int) $this->request->getAction();

        if ($id === (int) $this->usuarioLogado()['id']) {
            Session::set('msgError', 'Voce nao pode remover seu proprio nivel de admin.');
            return header("Location: /Admin/usuarios");
        }

        $this->usuarioModel->alterarNivel($id, UsuarioModel::NIVEL_COMUM);
        Session::set('msgSucesso', 'Usuario rebaixado a comum.');
        return header("Location: /Admin/usuarios");
    }

    /**
     * projetos
     * Visao geral de todos os projetos do sistema (independente de quem participa).
     *
     * @return void
     */
    public function projetos()
    {
        $projetos = $this->projetoModel->listarPorUsuario(0, true);

        return $this->view("adminProjetos", ['projetos' => $projetos]);
    }

    /**
     * contatos
     * Lista as mensagens enviadas pelo formulario de contato.
     * @return void
     */
    public function contatos()
    {
        $contatoModel = $this->model('Contato');
        $contatos = $contatoModel->listarTodos();

        return $this->view('adminContatos', ['contatos' => $contatos]);
    }

    public function termos()
    {
        $termos = $this->termoModel->listarTodos();

        return $this->view('adminTermos', ['termos' => $termos]);
    }

    public function salvarTermo()
    {
        $post = $this->request->getPost();
        $tipo = $post['tipo'] ?? '';
        $titulo = trim($post['titulo'] ?? '');
        $conteudo = trim($post['conteudo'] ?? '');
        $versao = trim($post['versao'] ?? '');
        $ativo = !empty($post['ativo']) ? 1 : 0;

        if (empty($tipo) || empty($titulo) || empty($conteudo)) {
            Session::set('msgError', 'Tipo, título e conteúdo são obrigatórios.');
            return header('Location: /Admin/termos');
        }

        if (empty($versao)) {
            $versao = date('YmdHis');
        }

        if ($ativo) {
            $this->termoModel->desativarAtivosPorTipo($tipo);
        }

        $novoId = $this->termoModel->inserir([
            'tipo' => $tipo,
            'titulo' => $titulo,
            'conteudo' => $conteudo,
            'versao' => $versao,
            'ativo' => $ativo,
        ]);

        if ($novoId > 0) {
            Session::set('msgSucesso', 'Termo salvo com sucesso.');
        } else {
            Session::set('msgError', 'Não foi possível salvar o termo.');
        }

        return header('Location: /Admin/termos');
    }

    public function verTermo()
    {
        $termoId = (int) $this->request->getAction();
        $termo = $this->termoModel->buscarPorId($termoId);

        if (empty($termo)) {
            Session::set('msgError', 'Termo não encontrado.');
            return header('Location: /Admin/termos');
        }

        return $this->view('adminTermo', ['termo' => $termo]);
    }

    public function ativarTermo()
    {
        $termoId = (int) $this->request->getAction();
        $termo = $this->termoModel->buscarPorId($termoId);

        if (empty($termo)) {
            Session::set('msgError', 'Termo não encontrado.');
            return header('Location: /Admin/termos');
        }

        $this->termoModel->desativarAtivosPorTipo($termo['tipo']);
        $this->termoModel->ativar($termoId);

        Session::set('msgSucesso', 'Termo ativado com sucesso.');
        return header('Location: /Admin/termos');
    }

    /**
     * verContato
     * Mostra os detalhes de uma mensagem de contato e permite resposta.
     * @return void
     */
    public function verContato()
    {
        $contatoId = (int) $this->request->getAction();
        $contatoModel = $this->model('Contato');
        $contato = $contatoModel->buscarPorId($contatoId);

        if (count($contato) === 0) {
            Session::set('msgError', 'Mensagem de contato nao encontrada.');
            return header('Location: /Admin/contatos');
        }

        return $this->view('adminContato', ['contato' => $contato]);
    }

    /**
     * responder
     * O admin responde a mensagem. Se o autor nao estava logado, recebe email.
     * @return void
     */
    public function responder()
    {
        $contatoId = (int) $this->request->getAction();
        $post = $this->request->getPost();
        $resposta = trim($post['resposta'] ?? '');
        $contatoModel = $this->model('Contato');
        $contato = $contatoModel->buscarPorId($contatoId);

        if (empty($resposta) || count($contato) === 0) {
            Session::set('msgError', 'O campo de resposta nao pode ficar vazio.');
            return header('Location: /Admin/verContato/' . $contatoId);
        }

        $adminId = (int) $this->usuarioLogado()['id'];
        $contatoModel->responder($contatoId, $adminId, $resposta);

        if (empty($contato['usuario_id'])) {
            $corpo = "<p>Olá " . htmlspecialchars($contato['nome']) . ",</p>"
                   . "<p>Sua mensagem foi respondida pelo administrador:</p>"
                   . "<p><strong>Assunto:</strong> " . htmlspecialchars($contato['assunto']) . "</p>"
                   . "<p><strong>Resposta:</strong><br>" . nl2br(htmlspecialchars($resposta)) . "</p>";
            Mailer::enviar($contato['email'], $contato['nome'], 'Resposta ao seu contato', $corpo);
        }

        Session::set('msgSucesso', 'Resposta enviada com sucesso.');
        return header('Location: /Admin/contatos');
    }
}
