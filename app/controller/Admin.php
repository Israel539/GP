<?php

namespace App\Controller;

use App\Library\Mailer;
use App\Library\Session;
use App\Model\ProjetoModel;
use App\Model\TermoModel;
use App\Model\UsuarioModel;

class Admin extends BaseController
{
    protected UsuarioModel $usuarioModel;
    protected ProjetoModel $projetoModel;
    protected TermoModel $termoModel;

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
        $contaModel = $this->model('Conta');

        return $this->view("adminIndex", [
            'totalUsuarios' => count($usuarios),
            'totalProjetos' => $this->projetoModel->contarTodos(),
            'totalContas'   => $contaModel->contarTodas(),
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
     * usuarioRecursos
     * URL: /Admin/usuarioRecursos/{usuarioId}
     * Mostra SO os IDs dos recursos de um usuario (projeto, conta, cartao,
     * compromisso, plano de compra) -- nunca nome, saldo ou conteudo. Serve
     * para o admin achar o ID certo antes de pedir acesso de suporte
     * (Admin::suporte()), sem precisar "espiar" o conteudo pra descobrir.
     *
     * @return void
     */
    public function usuarioRecursos()
    {
        $usuarioId = (int) $this->request->getAction();
        $usuarioAlvo = $this->usuarioModel->buscarPorId($usuarioId);

        if (count($usuarioAlvo) === 0) {
            Session::set('msgError', 'Usuario nao encontrado.');
            return header('Location: /Admin/usuarios');
        }

        $contaModel = $this->model('Conta');
        $cartaoModel = $this->model('CartaoCredito');
        $compromissoModel = $this->model('Compromisso');
        $planoCompraModel = $this->model('PlanoCompra');

        // So id + status/data -- nunca nome, descricao, saldo ou titulo.
        $projetos = array_map(fn($p) => ['id' => $p['id'], 'status' => $p['status'], 'papel' => $p['papel']],
            $this->projetoModel->listarPorUsuario($usuarioId));

        $contas = array_map(fn($c) => ['id' => $c['id'], 'tipo' => $c['tipo']],
            $contaModel->listarPorUsuario($usuarioId));

        $cartoes = array_map(fn($c) => ['id' => $c['id']],
            $cartaoModel->listarPorUsuario($usuarioId));

        $compromissos = array_map(fn($c) => ['id' => $c['id'], 'tipo' => $c['tipo'], 'status' => $c['status']],
            $compromissoModel->listarPorUsuario($usuarioId, 'todos'));

        $planosCompra = array_map(fn($p) => ['id' => $p['id'], 'status' => $p['status']],
            $planoCompraModel->listarPorUsuario($usuarioId, '', 1, 200, true));

        return $this->view('adminUsuarioRecursos', [
            'usuarioAlvo'  => $usuarioAlvo,
            'projetos'     => $projetos,
            'contas'       => $contas,
            'cartoes'      => $cartoes,
            'compromissos' => $compromissos,
            'planosCompra' => $planosCompra,
        ]);
    }

    /**
     * projetos
     * Antes mostrava nome/dono de TODOS os projetos do sistema -- isso violava
     * a privacidade de quem usa o app (admin nao precisa saber o nome do
     * projeto de ninguem). Agora so mostra o total agregado. Para inspecionar
     * um projeto especifico, use o fluxo auditado em suporte()/suporteAcessar().
     *
     * @return void
     */
    public function projetos()
    {
        return $this->view("adminProjetos", ['totalProjetos' => $this->projetoModel->contarTodos()]);
    }

    /**
     * suporte
     * Formulario para o admin pedir acesso pontual e auditado a um recurso
     * privado especifico (projeto, conta, cartao, fatura, compromisso ou
     * plano de compra), justificando o motivo.
     *
     * @return void
     */
    public function suporte()
    {
        return $this->view('adminSuporteForm');
    }

    /**
     * suporteAcessar
     * Grava a justificativa no log de auditoria (permanente), concede acesso
     * por tempo limitado (15min por padrao) guardado na sessao, e ja
     * redireciona direto pro recurso.
     *
     * @return void
     */
    public function suporteAcessar()
    {
        $post = $this->request->getPost();
        $admin = $this->usuarioLogado();

        $tipoRecurso = $post['tipo_recurso'] ?? '';
        $recursoId = (int) ($post['recurso_id'] ?? 0);
        $motivo = trim($post['motivo'] ?? '');

        $logModel = $this->model('LogAcessoSuporte');
        $resultado = $logModel->registrar((int) $admin['id'], $tipoRecurso, $recursoId, $motivo);

        if (!$resultado['ok']) {
            Session::set('msgError', $resultado['erro']);
            return header('Location: /Admin/suporte');
        }

        Session::set('acessoSuporte', [
            'id'           => $resultado['id'],
            'tipo_recurso' => $tipoRecurso,
            'recurso_id'   => $recursoId,
            'expira_em'    => $resultado['expiraEm'],
        ]);

        // Se o usuario tinha aberto um pedido de suporte pendente pra esse
        // mesmo recurso, fecha o ciclo sozinho -- ele ve no perfil dele que
        // foi atendido, sem o admin precisar fazer nada alem de conceder o
        // acesso normalmente.
        $this->model('SolicitacaoSuporte')->marcarAtendidaSeExistir(
            $tipoRecurso,
            $recursoId,
            $resultado['usuarioAlvoId'],
            (int) $admin['id']
        );

        Session::set('msgSucesso', 'Acesso de suporte concedido por 15 minutos. Essa acao foi registrada no log de auditoria. Uma caixa de chat vai aparecer para você conversar com o usuário durante o atendimento.');

        $rotas = [
            'projeto'      => "/Projeto/kanban/{$recursoId}",
            'conta'        => "/Transacao/extrato/{$recursoId}",
            'cartao'       => "/Cartao/faturas/{$recursoId}",
            'fatura'       => "/Cartao/faturaDetalhe/{$recursoId}",
            'compromisso'  => "/Agenda/form/{$recursoId}",
            'plano_compra' => "/PlanoCompra/ver/{$recursoId}",
        ];

        return header('Location: ' . ($rotas[$tipoRecurso] ?? '/Admin'));
    }

    /**
     * solicitacoesSuporte
     * URL: /Admin/solicitacoesSuporte
     * Fila de pedidos de suporte abertos pelos usuarios (SolicitacaoSuporte::enviar()),
     * ainda aguardando atendimento -- cada item tem um link pronto pra
     * /Admin/suporte ja preenchido com o tipo/recurso pedido (o form ja
     * suportava isso via query string, nao precisou mudar nada la).
     *
     * @return void
     */
    public function solicitacoesSuporte()
    {
        $pendentes = $this->model('SolicitacaoSuporte')->listarPendentes();

        return $this->view('adminSolicitacoesSuporte', ['pendentes' => $pendentes]);
    }

    /**
     * suporteHistorico
     * Transparencia: todo acesso de suporte ja concedido, por quem, a que
     * recurso, e o motivo declarado.
     *
     * @return void
     */
    public function suporteHistorico()
    {
        $logModel = $this->model('LogAcessoSuporte');
        return $this->view('adminSuporteHistorico', ['historico' => $logModel->listarHistorico()]);
    }

    /**
     * contatos
     * Lista as mensagens enviadas pelo formulario de contato.
     * @return void
     */
    public function contatos()
    {
        $mostrarExcluidos = $this->request->getQuery('show') === 'excluidos';
        $contatoModel = $this->model('Contato');
        $contatos = $contatoModel->listarTodos($mostrarExcluidos);

        return $this->view('adminContatos', [
            'contatos' => $contatos,
            'mostrarExcluidos' => $mostrarExcluidos,
        ]);
    }

    public function restaurarContatos()
    {
        $contatoModel = $this->model('Contato');
        $ok = $contatoModel->restaurarExcluidos();

        if ($ok) {
            Session::set('msgSucesso', 'Todos os contatos excluídos foram restaurados.');
        } else {
            Session::set('msgError', 'Nao foi possivel restaurar os contatos excluidos.');
        }

        return header('Location: /Admin/contatos?show=excluidos');
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

    public function excluirContato()
    {
        $contatoId = (int) $this->request->getAction();
        $contatoModel = $this->model('Contato');

        $ok = $contatoModel->excluir($contatoId);

        if ($ok) {
            Session::set('msgSucesso', 'Conversa respondida apagada com sucesso.');
        } else {
            Session::set('msgError', 'Nao foi possivel apagar esta conversa. Apenas mensagens respondidas podem ser excluidas.');
        }

        return header('Location: /Admin/contatos');
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
        $okResponder = $contatoModel->responder($contatoId, $adminId, $resposta);

        if (!$okResponder) {
            Session::set('msgError', 'Este contato ja foi respondido e o e-mail nao foi reenviado.');
            return header('Location: /Admin/verContato/' . $contatoId);
        }

        if (empty($contato['usuario_id'])) {
            $assuntoEmail = 'Resposta ao seu contato: ' . htmlspecialchars($contato['assunto']);
            $corpo = "<p>Olá " . htmlspecialchars($contato['nome']) . ",</p>"
                   . "<p>Recebemos seu contato e o administrador respondeu. Veja abaixo:</p>"
                   . "<p><strong>Assunto:</strong> " . htmlspecialchars($contato['assunto']) . "</p>"
                   . "<p><strong>Sua mensagem original:</strong><br>" . nl2br(htmlspecialchars($contato['mensagem'])) . "</p>"
                   . "<hr>"
                   . "<p><strong>Resposta:</strong><br>" . nl2br(htmlspecialchars($resposta)) . "</p>"
                   . "<p>Se você ainda tiver dúvidas, responda neste e-mail.</p>";

            $envio = Mailer::enviar($contato['email'], $contato['nome'], $assuntoEmail, $corpo);
            if (!$envio['ok']) {
                Session::set('msgError', 'Resposta salva, mas falha ao enviar e-mail: ' . $envio['erro']);
            }
        }

        if (empty($_SESSION['msgError'])) {
            Session::set('msgSucesso', 'Resposta enviada com sucesso.');
        } else {
            Session::set('msgSucesso', 'Resposta salva com sucesso.');
        }

        return header('Location: /Admin/contatos');
    }
}
