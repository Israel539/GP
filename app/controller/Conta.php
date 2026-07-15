<?php

namespace App\Controller;

use App\Library\Session;

class Conta extends BaseController
{
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = $this->model('Conta');
        $this->helper("crud");
    }

    /**
     * index
     * Lista as contas do usuario logado, ja com o saldo calculado (RN08).
     * Admin NAO ve conta/saldo de ninguem aqui -- ver Admin::suporteAcessar().
     *
     * @return void
     */
    public function index()
    {
        $usuario = $this->usuarioLogado();
        $contas = $this->model->listarPorUsuario((int) $usuario['id']);

        return $this->view("contas", ['contas' => $contas]);
    }

    /**
     * form
     *
     * @return void
     */
    public function form()
    {
        return $this->view("contaForm");
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
            return header("Location: /Conta/form");
        }

        $usuario = $this->usuarioLogado();

        $dados['saldo_inicial'] = str_replace(',', '.', $dados['saldo_inicial'] ?? '0');

        $contaId = $this->model->criar($dados, (int) $usuario['id']);

        if ($contaId > 0) {
            Session::set('msgSucesso', 'Conta criada com sucesso.');
            return header("Location: /Transacao/extrato/{$contaId}");
        }

        Session::set('msgError', 'Nao foi possivel criar a conta. Tente novamente.');
        return header("Location: /Conta/form");
    }
}
