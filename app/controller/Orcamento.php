<?php

namespace App\Controller;

use App\Library\Session;

class Orcamento extends BaseController
{
    protected $model;
    protected $categoriaModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = $this->model('Orcamento');
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
        $orcamentos = $this->model->listarComGasto((int) $usuario['id']);

        return $this->view('orcamentos', ['orcamentos' => $orcamentos]);
    }

    /**
     * form
     * @return void
     */
    public function form()
    {
        $usuario = $this->usuarioLogado();
        $categorias = $this->categoriaModel->listarDisponiveis((int) $usuario['id'], 'despesa');

        return $this->view('orcamentoForm', ['categorias' => $categorias]);
    }

    /**
     * salvar
     * @return void
     */
    public function salvar()
    {
        $dados = $this->request->getPost();
        $usuario = $this->usuarioLogado();

        $categoriaId = (int) ($dados['categoria_id'] ?? 0);

        // RN de seguranca: confere que essa categoria e do usuario (ou
        // padrao do sistema) antes de vincular o orcamento a ela -- sem
        // isso, dava pra forjar o categoria_id no formulario e vincular
        // (e depois visualizar) uma categoria de outro usuario. Ver
        // CategoriaModel::usuarioPodeUsar() para o detalhe.
        if (!$this->categoriaModel->usuarioPodeUsar($categoriaId, (int) $usuario['id'])) {
            Session::set('msgError', 'Categoria invalida.');
            return header('Location: /Orcamento/form');
        }

        $dados['valor_limite'] = str_replace(',', '.', $dados['valor_limite'] ?? '0');

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header('Location: /Orcamento/form');
        }

        $this->model->definir((int) $usuario['id'], $categoriaId, (float) $dados['valor_limite']);

        Session::set('msgSucesso', 'Orcamento salvo com sucesso.');
        return header('Location: /Orcamento');
    }

    /**
     * excluir
     * URL: /Orcamento/excluir/{id}
     * @return void
     */
    public function excluir()
    {
        $id = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        $ok = $this->model->excluir($id, (int) $usuario['id']);

        if ($ok) {
            Session::set('msgSucesso', 'Orcamento removido.');
        } else {
            Session::set('msgError', 'Nao foi possivel remover o orcamento.');
        }

        return header('Location: /Orcamento');
    }
}
