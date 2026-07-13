<?php

namespace App\Controller;

use App\Library\Session;

class Cartao extends BaseController
{
    protected $model;
    protected $contaModel;
    protected $faturaModel;
    protected $transacaoModel;
    protected $usuarioModel;

    public function __construct()
    {
        parent::__construct();
        $this->model          = $this->model('CartaoCredito');
        $this->contaModel     = $this->model('Conta');
        $this->faturaModel    = $this->model('Fatura');
        $this->transacaoModel = $this->model('Transacao');
        $this->usuarioModel   = $this->model('Usuario');
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
        $isAdmin  = $this->usuarioModel->isAdmin($usuario);

        if (!$isAdmin && !$this->model->usuarioEhDono($cartaoId, (int) $usuario['id'])) {
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
        $isAdmin  = $this->usuarioModel->isAdmin($usuario);

        if (!$isAdmin && !$this->faturaModel->usuarioEhDono($faturaId, (int) $usuario['id'])) {
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
        $isAdmin  = $this->usuarioModel->isAdmin($usuario);

        if (!$isAdmin && !$this->faturaModel->usuarioEhDono($faturaId, (int) $usuario['id'])) {
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
