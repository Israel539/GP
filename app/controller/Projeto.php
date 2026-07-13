<?php

namespace App\Controller;

use App\Library\Session;

class Projeto extends BaseController
{
    protected $model;
    protected $tarefaModel;
    protected $mensagemModel;
    protected $usuarioModel;

    public function __construct()
    {
        parent::__construct();
        $this->model         = $this->model('Projeto');
        $this->tarefaModel   = $this->model('Tarefa');
        $this->mensagemModel = $this->model('MensagemProjeto');
        $this->usuarioModel  = $this->model('Usuario');
        $this->helper("crud");
    }

    /**
     * index
     * Lista os projetos do usuario logado (ou todos, se for admin).
     *
     * @return void
     */
    public function index()
    {
        $usuario = $this->usuarioLogado();
        $isAdmin = $this->usuarioModel->isAdmin($usuario);

        $projetos = $this->model->listarPorUsuario((int) $usuario['id'], $isAdmin);

        return $this->view("projetos", ['projetos' => $projetos, 'isAdmin' => $isAdmin]);
    }

    /**
     * form
     * Formulario de criacao de um novo projeto.
     *
     * @return void
     */
    public function form()
    {
        return $this->view("projetoForm");
    }

    /**
     * salvar
     *
     * @return void
     */
    public function salvar()
    {
        $dados = $this->request->getPost();

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header("Location: /Projeto/form");
        }

        $usuario   = $this->usuarioLogado();
        $projetoId = $this->model->criar($dados, (int) $usuario['id']);

        if ($projetoId > 0) {
            Session::set('msgSucesso', 'Projeto criado com sucesso.');
            return header("Location: /Projeto/kanban/{$projetoId}");
        }

        Session::set('msgError', 'Nao foi possivel criar o projeto. Tente novamente.');
        return header("Location: /Projeto/form");
    }

    /**
     * kanban
     * Quadro do projeto: colunas a_fazer/em_andamento/concluido + colaboradores + chat.
     *
     * @return void
     */
    public function kanban()
    {
        $projetoId = (int) $this->request->getAction();
        $usuario   = $this->usuarioLogado();
        $isAdmin   = $this->usuarioModel->isAdmin($usuario);

        if (!$this->autorizado($projetoId, (int) $usuario['id'], $isAdmin)) {
            return $this->negarAcesso();
        }

        $projeto        = $this->model->buscarPorId($projetoId);
        $tarefas        = $this->tarefaModel->listarPorProjeto($projetoId);
        $colaboradores  = $this->model->listarColaboradores($projetoId);
        $mensagens      = $this->mensagemModel->listarPorProjeto($projetoId);

        return $this->view("projetoKanban", [
            'projeto'       => $projeto,
            'tarefas'       => $tarefas,
            'colaboradores' => $colaboradores,
            'mensagens'     => $mensagens,
        ]);
    }

    /**
     * convidar
     * Gera um convite por e-mail para outro usuario entrar no projeto.
     *
     * @return void
     */
    public function convidar()
    {
        $projetoId = (int) $this->request->getAction();
        $usuario   = $this->usuarioLogado();
        $isAdmin   = $this->usuarioModel->isAdmin($usuario);

        if (!$this->autorizado($projetoId, (int) $usuario['id'], $isAdmin)) {
            return $this->negarAcesso();
        }

        $post = $this->request->getPost();
        $email = $post['email_convidado'] ?? '';

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::set('msgError', 'Informe um e-mail valido para convidar.');
            return header("Location: /Projeto/kanban/{$projetoId}");
        }

        $token = $this->model->convidar($projetoId, (int) $usuario['id'], $email);

        $linkConvite = BASEURL . "Projeto/aceitar/{$token}";
        $corpoHtml = "
            <p>Ola,</p>
            <p><strong>" . htmlspecialchars($usuario['nome']) . "</strong> te convidou para participar do projeto
               <strong>" . htmlspecialchars($this->model->buscarPorId($projetoId)['nome']) . "</strong> no Projeto GP.</p>
            <p><a href=\"{$linkConvite}\">Clique aqui para aceitar o convite</a></p>
            <p>Se voce ainda nao tem uma conta, cadastre-se primeiro e depois abra este mesmo link.</p>
        ";

        $resultadoEnvio = \App\Library\Mailer::enviar(
            $email,
            $email,
            $usuario['nome'] . ' te convidou para um projeto no Projeto GP',
            $corpoHtml,
            $usuario['nome'] // nome de quem convidou aparece como remetente; a conta que envia continua a mesma
        );

        if ($resultadoEnvio['ok']) {
            Session::set('msgSucesso', "Convite enviado por e-mail para {$email}.");
        } else {
            // O convite (token) ja foi gravado no banco -- isso funciona
            // independente do e-mail. Mas o e-mail em si falhou, e o usuario
            // precisa saber disso (em vez de acreditar que foi enviado).
            // O motivo detalhado (ex: erro de autenticacao SMTP) vai pro
            // error_log do PHP, nao pra tela, para nao vazar detalhe tecnico.
            Session::set('msgError', "O convite foi registrado, mas o e-mail para {$email} NAO pode ser enviado agora. Veja o log do PHP para o motivo, ou compartilhe o link manualmente: <a href=\"{$linkConvite}\">{$linkConvite}</a>");
            // Session::set('msgError', "O convite foi registrado, mas o e-mail para {$email} NAO pode ser enviado agora. Veja o log do PHP para o motivo, ou compartilhe o link manualmente.");
        }   

        return header("Location: /Projeto/kanban/{$projetoId}");
    }

    /**
     * aceitar
     * Usuario logado aceita um convite recebido por e-mail (token na URL).
     *
     * @return void
     */
    public function aceitar()
    {
        $token   = (string) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        $ok = $this->model->aceitarConvite($token, (int) $usuario['id']);

        if ($ok) {
            Session::set('msgSucesso', 'Voce agora faz parte do projeto.');
        } else {
            Session::set('msgError', 'Convite invalido, expirado ou ja utilizado.');
        }

        return header("Location: /Projeto");
    }

    /**
     * concluir
     * RN05: ProjetoModel::concluir() ja bloqueia se houver tarefa pendente.
     *
     * @return void
     */
    public function concluir()
    {
        $projetoId = (int) $this->request->getAction();
        $usuario   = $this->usuarioLogado();
        $isAdmin   = $this->usuarioModel->isAdmin($usuario);

        if (!$this->autorizado($projetoId, (int) $usuario['id'], $isAdmin)) {
            return $this->negarAcesso();
        }

        $ok = $this->model->concluir($projetoId);

        if ($ok) {
            Session::set('msgSucesso', 'Projeto concluido.');
        } else {
            Session::set('msgError', 'Ainda ha tarefas pendentes neste projeto (RN05).');
        }

        return header("Location: /Projeto/kanban/{$projetoId}");
    }

    /**
     * mensagem
     * Envia uma mensagem no chat interno do projeto.
     *
     * @return void
     */
    public function mensagem()
    {
        $projetoId = (int) $this->request->getAction();
        $usuario   = $this->usuarioLogado();
        $isAdmin   = $this->usuarioModel->isAdmin($usuario);

        if (!$this->autorizado($projetoId, (int) $usuario['id'], $isAdmin)) {
            return $this->negarAcesso();
        }

        $post = $this->request->getPost();
        $texto = trim($post['mensagem'] ?? '');

        if ($texto !== '') {
            $this->mensagemModel->enviar($projetoId, (int) $usuario['id'], $texto);
        }

        return header("Location: /Projeto/kanban/{$projetoId}");
    }

    /**
     * autorizado
     * RN de autorizacao: so quem participa do projeto (dono/colaborador) ou
     * um admin do sistema pode ver/mexer nele.
     *
     * @param int $projetoId
     * @param int $usuarioId
     * @param bool $isAdmin
     * @return bool
     */
    protected function autorizado(int $projetoId, int $usuarioId, bool $isAdmin): bool
    {
        return $isAdmin || $this->model->usuarioParticipa($projetoId, $usuarioId);
    }

    /**
     * negarAcesso
     *
     * @return void
     */
    protected function negarAcesso()
    {
        http_response_code(403);
        Session::set('msgError', 'Voce nao tem acesso a este projeto.');
        return header("Location: /Projeto");
    }
}
