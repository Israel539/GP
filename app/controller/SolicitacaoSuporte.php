<?php

namespace App\Controller;

use App\Library\Session;

class SolicitacaoSuporte extends BaseController
{
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = $this->model('SolicitacaoSuporte');
        $this->helper("crud");
    }

    /**
     * form
     * URL: /SolicitacaoSuporte/form
     * Formulario pro usuario pedir suporte indicando ONDE precisa de ajuda
     * -- monta a lista de recursos dele mesmo (projetos que e dono, contas,
     * cartoes, compromissos, planos de compra), pra ele escolher pelo nome
     * em vez de digitar um ID de cabeca.
     *
     * @return void
     */
    public function form()
    {
        $usuario   = $this->usuarioLogado();
        $usuarioId = (int) $usuario['id'];

        // So projetos onde o usuario e DONO entram na lista: e o dono (nao
        // um colaborador) que o fluxo de suporte trata como "alvo" do
        // acesso (mesma regra de LogAcessoSuporteModel/SolicitacaoSuporteModel).
        $projetos = array_values(array_filter(
            $this->model('Projeto')->listarPorUsuario($usuarioId),
            fn($p) => $p['papel'] === \App\Model\ProjetoModel::PAPEL_DONO
        ));

        $contas       = $this->model('Conta')->listarPorUsuario($usuarioId);
        $cartoes      = $this->model('CartaoCredito')->listarPorUsuario($usuarioId);
        $compromissos = array_reverse($this->model('Compromisso')->listarPorUsuario($usuarioId));
        $planosCompra = $this->model('PlanoCompra')->listarPorUsuario($usuarioId, '', 1, 200, false, true);

        $minhasSolicitacoes = $this->model->listarPorUsuario($usuarioId);

        return $this->view('solicitacaoSuporteForm', [
            'projetos'           => $projetos,
            'contas'             => $contas,
            'cartoes'            => $cartoes,
            'compromissos'       => $compromissos,
            'planosCompra'       => $planosCompra,
            'minhasSolicitacoes' => $minhasSolicitacoes,
        ]);
    }

    /**
     * enviar
     * URL: /SolicitacaoSuporte/enviar (POST)
     *
     * @return void
     */
    public function enviar()
    {
        $usuario = $this->usuarioLogado();
        $post    = $this->request->getPost();

        $tipoRecurso = $post['tipo_recurso'] ?? '';
        $recursoId   = (int) ($post['recurso_id'] ?? 0);
        $mensagem    = $post['mensagem'] ?? '';

        $resultado = $this->model->criar((int) $usuario['id'], $tipoRecurso, $recursoId, $mensagem);

        if (!$resultado['ok']) {
            Session::set('msgError', $resultado['erro']);
        } else {
            Session::set('msgSucesso', 'Pedido de suporte enviado. Um administrador vai entrar em contato assim que possível.');
        }

        return header('Location: /SolicitacaoSuporte/form');
    }

    /**
     * cancelar
     * URL: /SolicitacaoSuporte/cancelar/{id} (POST)
     * Usuario desiste do proprio pedido (ainda pendente) -- ex: resolveu
     * sozinho, ou abriu por engano.
     *
     * @return void
     */
    public function cancelar()
    {
        $id      = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        $this->model->cancelar($id, (int) $usuario['id']);

        Session::set('msgSucesso', 'Pedido cancelado.');
        return header('Location: /SolicitacaoSuporte/form');
    }
}
