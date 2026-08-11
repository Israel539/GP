<?php

namespace App\Controller;

use App\Library\Session;

class Usuario extends BaseController
{
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = $this->model('Usuario');
    }

    /**
     * perfil
     * URL: /Usuario/perfil
     * Mostra os dados do usuário logado e a opção de excluir a conta.
     *
     * @return void
     */
    public function perfil()
    {
        $usuario = $this->usuarioLogado();

        if (!is_array($usuario) || empty($usuario['id'])) {
            Session::set('msgError', 'Para acessar seu perfil, faça login primeiro.');
            return header('Location: /Login');
        }

        $usuarioCompleto = $this->model->buscarPorId((int) $usuario['id']);
        return $this->view('usuarioPerfil', ['usuario' => $usuarioCompleto]);
    }

    /**
     * excluirConta
     * URL: /Usuario/excluirConta
     * Exclusão definitiva da conta do usuário atual.
     *
     * @return void
     */
    public function excluirConta()
    {
        $usuario = $this->usuarioLogado();

        if (!is_array($usuario) || empty($usuario['id'])) {
            Session::set('msgError', 'Para excluir a conta, faça login primeiro.');
            return header('Location: /Login');
        }

        $usuarioId = (int) $usuario['id'];
        $ok = $this->model->deletar($usuarioId);

        if ($ok) {
            Session::destroy(SESSION_USER_KEY);
            Session::set('msgSucesso', 'Sua conta foi excluída com sucesso.');
            return header('Location: /Home');
        }

        Session::set('msgError', 'Não foi possível excluir sua conta. Tente novamente.');
        return header('Location: /Usuario/perfil');
    }
}
