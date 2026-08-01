<?php

namespace App\Controller;

use App\Library\Session;

class Recorrencia extends BaseController
{
    protected $model;
    protected $contaModel;
    protected $categoriaModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = $this->model('Recorrencia');
        $this->contaModel = $this->model('Conta');
        $this->categoriaModel = $this->model('Categoria');
        $this->helper("crud");
    }

    /**
     * index
     * @return void
     */
    public function index()
    {
        $usuario = $this->usuarioLogado();
        $recorrencias = $this->model->listarPorUsuario((int) $usuario['id']);

        return $this->view('recorrencias', ['recorrencias' => $recorrencias]);
    }

    /**
     * form
     * @return void
     */
    public function form()
    {
        $usuario = $this->usuarioLogado();
        $contas = $this->contaModel->listarPorUsuario((int) $usuario['id']);
        $categorias = $this->categoriaModel->listarDisponiveis((int) $usuario['id']);

        return $this->view('recorrenciaForm', ['contas' => $contas, 'categorias' => $categorias]);
    }

    /**
     * editar
     * URL: /Recorrencia/editar/{id}
     * @return void
     */
    public function editar()
    {
        $id = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        if (!$this->model->pertenceAoUsuario($id, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $recorrencia = $this->model->buscarPorId($id);
        $contas = $this->contaModel->listarPorUsuario((int) $usuario['id']);
        $categorias = $this->categoriaModel->listarDisponiveis((int) $usuario['id']);

        return $this->view('recorrenciaForm', ['recorrencia' => $recorrencia, 'contas' => $contas, 'categorias' => $categorias]);
    }

    /**
     * salvar
     * @return void
     */
    public function salvar()
    {
        $dados = $this->request->getPost();
        $usuario = $this->usuarioLogado();

        $contaId = (int) ($dados['conta_id'] ?? 0);

        if (!$this->contaModel->usuarioEhDono($contaId, (int) $usuario['id'])) {
            Session::set('msgError', 'Conta invalida.');
            return header('Location: /Recorrencia/form');
        }

        $dados['valor'] = str_replace(',', '.', $dados['valor'] ?? '0');

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header('Location: /Recorrencia/form');
        }

        $this->model->criar($dados, $contaId);

        Session::set('msgSucesso', 'Recorrencia criada com sucesso.');
        return header('Location: /Recorrencia');
    }

    /**
     * atualizar
     * @return void
     */
    public function atualizar()
    {
        $dados = $this->request->getPost();
        $usuario = $this->usuarioLogado();
        $id = (int) ($dados['id'] ?? 0);

        if (!$this->model->pertenceAoUsuario($id, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $dados['valor'] = str_replace(',', '.', $dados['valor'] ?? '0');

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header("Location: /Recorrencia/editar/{$id}");
        }

        $this->model->atualizar($id, $dados);

        Session::set('msgSucesso', 'Recorrencia atualizada.');
        return header('Location: /Recorrencia');
    }

    /**
     * ativar / desativar
     * URL: /Recorrencia/alternar/{id}
     * @return void
     */
    public function alternar()
    {
        $id = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        if (!$this->model->pertenceAoUsuario($id, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $recorrencia = $this->model->buscarPorId($id);
        $this->model->alternarAtivo($id, !$recorrencia['ativo']);

        Session::set('msgSucesso', $recorrencia['ativo'] ? 'Recorrencia pausada.' : 'Recorrencia reativada.');
        return header('Location: /Recorrencia');
    }

    /**
     * excluir
     * URL: /Recorrencia/excluir/{id}
     * @return void
     */
    public function excluir()
    {
        $id = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        if (!$this->model->pertenceAoUsuario($id, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $this->model->excluir($id);

        Session::set('msgSucesso', 'Recorrencia excluida.');
        return header('Location: /Recorrencia');
    }

    /**
     * negarAcesso
     * @return void
     */
    protected function negarAcesso()
    {
        http_response_code(403);
        Session::set('msgError', 'Voce nao tem acesso a esta recorrencia.');
        return header('Location: /Recorrencia');
    }
}
