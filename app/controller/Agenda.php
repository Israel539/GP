<?php

namespace App\Controller;

use App\Library\Session;

class Agenda extends BaseController
{
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = $this->model('Compromisso');
        $this->helper("crud");
    }

    /**
     * index
     * URL: /Agenda?filtro=hoje|semana|todos
     *
     * @return void
     */
    public function index()
    {
        $usuario = $this->usuarioLogado();
        $filtro  = $_GET['filtro'] ?? 'todos';

        if (!in_array($filtro, ['hoje', 'semana', 'todos'], true)) {
            $filtro = 'todos';
        }

        $compromissos = $this->model->listarPorUsuario((int) $usuario['id'], $filtro);

        return $this->view("agenda", ['compromissos' => $compromissos, 'filtro' => $filtro]);
    }

    /**
     * form
     * URL: /Agenda/form ou /Agenda/form/{id} (edicao)
     *
     * @return void
     */
    public function form()
    {
        $idParaEditar = $this->request->getAction();
        $compromisso  = null;

        if ($idParaEditar !== "" && $idParaEditar !== null) {
            $usuario = $this->usuarioLogado();

            if (!$this->model->usuarioEhDono((int) $idParaEditar, (int) $usuario['id'])) {
                return $this->negarAcesso();
            }

            $compromisso = $this->model->buscarPorId((int) $idParaEditar);
        }

        return $this->view("compromissoForm", ['compromisso' => $compromisso]);
    }

    /**
     * salvar
     * Cria OU atualiza, dependendo se veio um campo "id" oculto no form.
     *
     * @return void
     */
    public function salvar()
    {
        $dados   = $this->request->getPost();
        $usuario = $this->usuarioLogado();

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            $redirectId = !empty($dados['id']) ? "/{$dados['id']}" : "";
            return header("Location: /Agenda/form{$redirectId}");
        }

        $idExistente = !empty($dados['id']) ? (int) $dados['id'] : null;

        if ($idExistente !== null) {
            if (!$this->model->usuarioEhDono($idExistente, (int) $usuario['id'])) {
                return $this->negarAcesso();
            }

            $resultado = $this->model->atualizar($idExistente, $dados, (int) $usuario['id']);
        } else {
            $resultado = $this->model->criar($dados, (int) $usuario['id']);
        }

        if (!$resultado['ok']) {
            Session::set('msgError', $resultado['erro']);
            $redirectId = $idExistente !== null ? "/{$idExistente}" : "";
            return header("Location: /Agenda/form{$redirectId}");
        }

        Session::set('msgSucesso', $idExistente !== null ? 'Compromisso atualizado.' : 'Compromisso criado.');
        return header("Location: /Agenda");
    }

    /**
     * concluir
     * URL: /Agenda/concluir/{id}
     *
     * @return void
     */
    public function concluir()
    {
        $id      = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        if (!$this->model->usuarioEhDono($id, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $this->model->concluir($id, (int) $usuario['id']);
        Session::set('msgSucesso', 'Compromisso marcado como concluido.');
        return header("Location: /Agenda");
    }

    /**
     * cancelar
     * URL: /Agenda/cancelar/{id}
     *
     * @return void
     */
    public function cancelar()
    {
        $id      = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        if (!$this->model->usuarioEhDono($id, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $this->model->cancelar($id, (int) $usuario['id']);
        Session::set('msgSucesso', 'Compromisso cancelado.');
        return header("Location: /Agenda");
    }

    /**
     * excluir
     * URL: /Agenda/excluir/{id}
     *
     * @return void
     */
    public function excluir()
    {
        $id      = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        if (!$this->model->usuarioEhDono($id, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $this->model->excluir($id, (int) $usuario['id']);
        Session::set('msgSucesso', 'Compromisso excluido.');
        return header("Location: /Agenda");
    }

    /**
     * negarAcesso
     *
     * @return void
     */
    protected function negarAcesso()
    {
        http_response_code(403);
        Session::set('msgError', 'Voce nao tem acesso a este compromisso.');
        return header("Location: /Agenda");
    }
}
