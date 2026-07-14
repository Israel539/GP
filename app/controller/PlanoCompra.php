<?php

namespace App\Controller;

use App\Library\Session;

class PlanoCompra extends BaseController
{
    protected $model;
    protected $usuarioModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = $this->model('PlanoCompra');
        $this->usuarioModel = $this->model('Usuario');
    }

    public function index()
    {
        $usuario = $this->usuarioLogado();
        $mostrarExcluidos = $this->request->getQuery('show') === 'excluidos';
        $search = trim((string) $this->request->getQuery('search') ?? '');
        $page = max(1, (int) ($this->request->getQuery('page') ?? 1));
        $perPage = 6;

        $planos = $this->model->listarPorUsuario((int) $usuario['id'], $search, $page, $perPage, $mostrarExcluidos);
        $totalPlanos = $this->model->contarPorUsuario((int) $usuario['id'], $search, $mostrarExcluidos);
        $excluidosCount = $this->model->contarExcluidosPorUsuario((int) $usuario['id']);
        $totalPages = (int) ceil($totalPlanos / $perPage);

        return $this->view('planosCompra', [
            'planos' => $planos,
            'mostrarExcluidos' => $mostrarExcluidos,
            'excluidosCount' => $excluidosCount,
            'search' => $search,
            'page' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    public function form()
    {
        return $this->view('planoCompraForm');
    }

    public function editar()
    {
        $planoId = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        $plano = $this->model->buscarPorId($planoId);

        if (empty($plano) || (int) $plano['usuario_id'] !== (int) $usuario['id']) {
            http_response_code(403);
            Session::set('msgError', 'Plano nao encontrado ou sem permissao.');
            return header('Location: /PlanoCompra');
        }

        return $this->view('planoCompraForm', ['plano' => $plano]);
    }

    public function atualizar()
    {
        $dados = $this->request->getPost();
        $usuario = $this->usuarioLogado();

        $id = (int) ($dados['id'] ?? 0);
        if ($id <= 0) {
            Session::set('msgError', 'ID do plano invalido.');
            return header('Location: /PlanoCompra');
        }

        $plano = $this->model->buscarPorId($id);
        if (empty($plano) || (int) $plano['usuario_id'] !== (int) $usuario['id']) {
            http_response_code(403);
            Session::set('msgError', 'Plano nao encontrado ou sem permissao.');
            return header('Location: /PlanoCompra');
        }

        $dados['valor_total'] = str_replace(',', '.', $dados['valor_total'] ?? '0');

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header('Location: /PlanoCompra/editar/' . $id);
        }

        $ok = $this->model->atualizar($id, $dados);

        if ($ok) {
            Session::set('msgSucesso', 'Plano atualizado com sucesso.');
        } else {
            Session::set('msgError', 'Nao foi possivel atualizar o plano.');
        }

        return header('Location: /PlanoCompra');
    }

    public function excluir()
    {
        $planoId = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        $plano = $this->model->buscarPorId($planoId);

        if (empty($plano) || (int) $plano['usuario_id'] !== (int) $usuario['id']) {
            http_response_code(403);
            Session::set('msgError', 'Plano nao encontrado ou sem permissao.');
            return header('Location: /PlanoCompra');
        }

        $ok = $this->model->deletar($planoId);

        if ($ok) {
            Session::set('msgSucesso', 'Plano excluido com sucesso.');
        } else {
            Session::set('msgError', 'Nao foi possivel excluir o plano.');
        }

        return header('Location: /PlanoCompra');
    }

    public function restaurar()
    {
        $planoId = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        $plano = $this->model->buscarPorId($planoId);

        if (empty($plano) || (int) $plano['usuario_id'] !== (int) $usuario['id']) {
            http_response_code(403);
            Session::set('msgError', 'Plano nao encontrado ou sem permissao.');
            return header('Location: /PlanoCompra');
        }

        $ok = $this->model->restaurar($planoId);

        if ($ok) {
            Session::set('msgSucesso', 'Plano restaurado com sucesso.');
        } else {
            Session::set('msgError', 'Nao foi possivel restaurar o plano (provavelmente o periodo de restauração expirou).');
        }

        return header('Location: /PlanoCompra');
    }

    public function restaurarTodos()
    {
        $usuario = $this->usuarioLogado();
        $ok = $this->model->restaurarTodosPorUsuario((int) $usuario['id']);

        if ($ok) {
            Session::set('msgSucesso', 'Todos os planos excluídos foram restaurados com sucesso.');
        } else {
            Session::set('msgError', 'Nao foi possivel restaurar os planos excluidos.');
        }

        return header('Location: /PlanoCompra?show=excluidos');
    }

    public function salvar()
    {
        $dados = $this->request->getPost();
        $usuario = $this->usuarioLogado();

        $dados['valor_total'] = str_replace(',', '.', $dados['valor_total'] ?? '0');

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header('Location: /PlanoCompra/form');
        }

        $planoId = $this->model->criar($dados, (int) $usuario['id']);

        if ($planoId > 0) {
            Session::set('msgSucesso', 'Plano de compra salvo com sucesso.');
            return header('Location: /PlanoCompra');
        }

        Session::set('msgError', 'Nao foi possivel salvar o plano. Tente novamente.');
        return header('Location: /PlanoCompra/form');
    }

    public function ver()
    {
        $planoId = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        $plano = $this->model->buscarPorId($planoId);

        if (empty($plano) || (int) $plano['usuario_id'] !== (int) $usuario['id']) {
            http_response_code(403);
            Session::set('msgError', 'Plano de compra nao encontrado ou sem permissao.');
            return header('Location: /PlanoCompra');
        }

        return $this->view('planoCompraDetalhe', ['plano' => $plano]);
    }

    public function concluir()
    {
        $planoId = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        $plano = $this->model->buscarPorId($planoId);

        if (empty($plano) || (int) $plano['usuario_id'] !== (int) $usuario['id']) {
            http_response_code(403);
            Session::set('msgError', 'Plano de compra nao encontrado ou sem permissao.');
            return header('Location: /PlanoCompra');
        }

        $ok = $this->model->finalizar($planoId);

        if ($ok) {
            Session::set('msgSucesso', 'Plano marcado como concluido.');
        } else {
            Session::set('msgError', 'Nao foi possivel concluir o plano.');
        }

        return header('Location: /PlanoCompra');
    }

    public function cancelar()
    {
        $planoId = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        $plano = $this->model->buscarPorId($planoId);

        if (empty($plano) || (int) $plano['usuario_id'] !== (int) $usuario['id']) {
            http_response_code(403);
            Session::set('msgError', 'Plano de compra nao encontrado ou sem permissao.');
            return header('Location: /PlanoCompra');
        }

        $ok = $this->model->cancelar($planoId);

        if ($ok) {
            Session::set('msgSucesso', 'Plano cancelado.');
        } else {
            Session::set('msgError', 'Nao foi possivel cancelar o plano.');
        }

        return header('Location: /PlanoCompra');
    }
}
