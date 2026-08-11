<?php

namespace App\Controller;

use App\Library\Session;

class Cartao extends BaseController
{
    protected $model;
    protected $contaModel;
    protected $faturaModel;
    protected $transacaoModel;

    public function __construct()
    {
        parent::__construct();
        $this->model          = $this->model('CartaoCredito');
        $this->contaModel     = $this->model('Conta');
        $this->faturaModel    = $this->model('Fatura');
        $this->transacaoModel = $this->model('Transacao');
        $this->helper("crud");
    }

    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        $usuario = $this->usuarioLogado();
        $cartoes = $this->model->listarPorUsuario((int) $usuario['id']);

        return $this->view("cartoes", ['cartoes' => $cartoes]);
    }

    /**
     * form
     *
     * @return void
     */
    public function form()
    {
        $usuario = $this->usuarioLogado();
        $contas  = $this->contaModel->listarPorUsuario((int) $usuario['id']);

        return $this->view("cartaoForm", ['contas' => $contas]);
    }

    /**
     * salvar
     *
     * @return void
     */
    public function salvar()
    {
        $dados   = $this->request->getPost();
        $usuario = $this->usuarioLogado();

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header("Location: /Cartao/form");
        }

        $contaPagadoraId = (int) ($dados['conta_pagadora_id'] ?? 0);

        if (!$this->contaModel->usuarioEhDono($contaPagadoraId, (int) $usuario['id'])) {
            Session::set('msgError', 'Conta pagadora invalida.');
            return header("Location: /Cartao/form");
        }

        if (!empty($dados['limite'])) {
            $dados['limite'] = str_replace(',', '.', $dados['limite']);
        }

        $cartaoId = $this->model->criar($dados, $contaPagadoraId);

        if ($cartaoId > 0) {
            Session::set('msgSucesso', 'Cartao cadastrado com sucesso.');
            return header("Location: /Cartao");
        }

        Session::set('msgError', 'Nao foi possivel cadastrar o cartao. Tente novamente.');
        return header("Location: /Cartao/form");
    }

    /**
     * faturas
     * URL: /Cartao/faturas/{cartaoId}
     *
     * @return void
     */
    public function faturas()
    {
        $cartaoId = (int) $this->request->getAction();
        $usuario  = $this->usuarioLogado();

        if (!$this->model->usuarioEhDono($cartaoId, (int) $usuario['id']) && !$this->temAcessoSuporteAtivo('cartao', $cartaoId)) {
            return $this->negarAcesso();
        }

        $cartao  = $this->model->buscarPorId($cartaoId);
        $faturas = $this->faturaModel->listarPorCartao($cartaoId);

        return $this->view("faturas", ['cartao' => $cartao, 'faturas' => $faturas]);
    }

    /**
     * faturaDetalhe
     * URL: /Cartao/faturaDetalhe/{faturaId}
     *
     * @return void
     */
    public function faturaDetalhe()
    {
        $faturaId = (int) $this->request->getAction();
        $usuario  = $this->usuarioLogado();

        if (!$this->faturaModel->usuarioEhDono($faturaId, (int) $usuario['id']) && !$this->temAcessoSuporteAtivo('fatura', $faturaId)) {
            return $this->negarAcesso();
        }

        $fatura     = $this->faturaModel->buscarPorId($faturaId);
        $transacoes = $this->transacaoModel->listarPorFatura($faturaId);

        return $this->view("faturaDetalhe", ['fatura' => $fatura, 'transacoes' => $transacoes]);
    }

    /**
     * pagarFatura
     * URL: /Cartao/pagarFatura/{faturaId}
     * RN09: FaturaModel::pagar() cria a transacao de debito na conta
     * pagadora e so ai o valor sai de fato do saldo.
     *
     * @return void
     */
    public function pagarFatura()
    {
        $faturaId = (int) $this->request->getAction();
        $usuario  = $this->usuarioLogado();

        // Pagar fatura e uma ACAO financeira -- exige ser o dono de verdade,
        // acesso de suporte nunca da bypass aqui (so serve para inspecionar).
        if (!$this->faturaModel->usuarioEhDono($faturaId, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $fatura = $this->faturaModel->buscarPorId($faturaId);
        $ok = $this->faturaModel->pagar($faturaId);

        if ($ok) {
            Session::set('msgSucesso', 'Fatura paga com sucesso.');
        } else {
            Session::set('msgError', 'Nao foi possivel pagar esta fatura (ja paga ou nao encontrada).');
        }

        return header("Location: /Cartao/faturas/{$fatura['cartao_id']}");
    }

    /**
     * editar
     * URL: /Cartao/editar/{cartaoId}
     *
     * @return void
     */
    public function editar()
    {
        $cartaoId = (int) $this->request->getAction();
        $usuario  = $this->usuarioLogado();

        if (!$this->model->usuarioEhDono($cartaoId, (int) $usuario['id']) && !$this->temAcessoSuporteAtivo('cartao', $cartaoId)) {
            return $this->negarAcesso();
        }

        $cartao = $this->model->buscarPorId($cartaoId);
        $contas = $this->contaModel->listarPorUsuario((int) $usuario['id']);

        return $this->view('cartaoForm', ['contas' => $contas, 'cartao' => $cartao]);
    }

    /**
     * atualizar
     * Recebe POST para atualizar um cartao existente
     *
     * @return void
     */
    public function atualizar()
    {
        $dados   = $this->request->getPost();
        $usuario = $this->usuarioLogado();

        $id = (int) ($dados['id'] ?? 0);
        if ($id <= 0) {
            Session::set('msgError', 'ID do cartao invalido.');
            return header('Location: /Cartao');
        }

        if (!$this->model->usuarioEhDono($id, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header("Location: /Cartao/editar/{$id}");
        }

        $contaPagadoraId = (int) ($dados['conta_pagadora_id'] ?? 0);
        if (!$this->contaModel->usuarioEhDono($contaPagadoraId, (int) $usuario['id'])) {
            Session::set('msgError', 'Conta pagadora invalida.');
            return header("Location: /Cartao/editar/{$id}");
        }

        if (!empty($dados['limite'])) {
            $dados['limite'] = str_replace(',', '.', $dados['limite']);
        }

        $ok = $this->model->atualizar($id, $dados, $contaPagadoraId);

        if ($ok) {
            Session::set('msgSucesso', 'Cartao atualizado com sucesso.');
        } else {
            Session::set('msgError', 'Nao foi possivel atualizar o cartao.');
        }

        return header('Location: /Cartao');
    }

    /**
     * deletar
     * URL: /Cartao/deletar/{cartaoId}
     *
     * @return void
     */
    public function deletar()
    {
        $cartaoId = (int) $this->request->getAction();
        $usuario  = $this->usuarioLogado();

        if (!$this->model->usuarioEhDono($cartaoId, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        // Soft-delete: marca excluido_em. O model já implementa isso.
        $ok = $this->model->deletar($cartaoId);

        if ($ok) {
            Session::set('msgSucesso', 'Cartao excluido com sucesso.');
        } else {
            Session::set('msgError', 'Nao foi possivel excluir o cartao.');
        }

        return header('Location: /Cartao');
    }

    /**
     * negarAcesso
     *
     * @return void
     */
    protected function negarAcesso()
    {
        http_response_code(403);
        Session::set('msgError', 'Voce nao tem acesso a este cartao.');
        return header("Location: /Cartao");
    }
}
