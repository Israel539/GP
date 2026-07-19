<?php

namespace App\Controller;

use App\Library\Session;

class Categoria extends BaseController
{
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = $this->model('Categoria');
        $this->helper("crud");
    }

    /**
     * index
     * Lista as categorias que o usuario pode usar: as proprias + as padrao
     * do sistema (usuario_id NULL).
     *
     * @return void
     */
    public function index()
    {
        $usuario = $this->usuarioLogado();
        $categorias = $this->model->listarDisponiveis((int) $usuario['id']);

        return $this->view('categorias', ['categorias' => $categorias, 'meuId' => (int) $usuario['id']]);
    }

    /**
     * form
     *
     * @return void
     */
    public function form()
    {
        return $this->view('categoriaForm');
    }

    /**
     * salvar
     *
     * @return void
     */
    public function salvar()
    {
        $dados = $this->request->getPost();
        $usuario = $this->usuarioLogado();

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header('Location: /Categoria/form');
        }

        $this->model->criar($dados, (int) $usuario['id']);

        Session::set('msgSucesso', 'Categoria criada com sucesso.');
        return header('Location: /Categoria');
    }

    /**
     * excluir
     * URL: /Categoria/excluir/{id}
     * So exclui se a categoria for do proprio usuario (Model ja garante isso
     * -- categoria padrao do sistema, usuario_id NULL, nunca e removida por
     * um usuario comum).
     *
     * @return void
     */
    public function excluir()
    {
        $id = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        $ok = $this->model->excluir($id, (int) $usuario['id']);

        if ($ok) {
            Session::set('msgSucesso', 'Categoria excluida.');
        } else {
            Session::set('msgError', 'Nao foi possivel excluir -- categorias padrao do sistema nao podem ser removidas.');
        }

        return header('Location: /Categoria');
    }
}