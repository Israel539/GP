<?php

namespace App\Controller;

use App\Library\Session;

class Projeto extends BaseController
{
    protected $model;
    protected $tarefaModel;
    protected $mensagemModel;

    public function __construct()
    {
        parent::__construct();
        $this->model         = $this->model('Projeto');
        $this->tarefaModel   = $this->model('Tarefa');
        $this->mensagemModel = $this->model('MensagemProjeto');
        $this->helper("crud");
    }

    /**
     * index
     * Lista os projetos do usuario logado. Admin NAO ve projeto de ninguem
     * aqui -- ve so os proprios (se tiver algum) -- ver Admin::suporteAcessar()
     * para acesso pontual e auditado a um projeto especifico.
     *
     * @return void
     */
    public function index()
    {
        $usuario = $this->usuarioLogado();
        $projetos = $this->model->listarPorUsuario((int) $usuario['id']);

        return $this->view("projetos", ['projetos' => $projetos]);
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

        if (!$this->podeVisualizar($projetoId, (int) $usuario['id'])) {
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

        if (!$this->podeGerenciar($projetoId, (int) $usuario['id'])) {
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
            Session::set('msgError', "O convite foi registrado, mas o e-mail para {$email} NAO pode ser enviado agora. Veja o log do PHP para o motivo, ou compartilhe o link manualmente: <a href=\"{$linkConvite}\">{$linkConvite}</a>");
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

        if (!$this->podeGerenciar($projetoId, (int) $usuario['id'])) {
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

        if (!$this->podeGerenciar($projetoId, (int) $usuario['id'])) {
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
     * podeVisualizar
     * Quem participa do projeto (dono/colaborador) SEMPRE pode ver. Um admin
     * com concessao de suporte ATIVA e ESPECIFICA para este projeto (ver
     * Admin::suporteAcessar()) tambem pode -- mas so ver, nunca agir (ver
     * podeGerenciar abaixo). Isso e o que abre o Kanban em modo leitura.
     *
     * @param int $projetoId
     * @param int $usuarioId
     * @return bool
     */
    protected function podeVisualizar(int $projetoId, int $usuarioId): bool
    {
        return $this->model->usuarioParticipa($projetoId, $usuarioId)
            || $this->temAcessoSuporteAtivo('projeto', $projetoId);
    }

    /**
     * podeGerenciar
     * Acoes que MUDAM algo ou falam em nome de alguem (convidar, concluir,
     * mandar mensagem no chat) exigem participacao de verdade no projeto.
     * Acesso de suporte NUNCA da bypass aqui -- suporte e so para inspecionar,
     * nao para agir como se fosse o usuario.
     *
     * @param int $projetoId
     * @param int $usuarioId
     * @return bool
     */
    protected function podeGerenciar(int $projetoId, int $usuarioId): bool
    {
        return $this->model->usuarioParticipa($projetoId, $usuarioId);
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
