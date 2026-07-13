<?php

namespace App\Controller;

use App\Library\Session;

class Cadastro extends BaseController
{
    protected $model;

    /**
     * construct
     */
    public function __construct()
    {
        parent::__construct();                 // Csrf + verificarAutenticacao (Cadastro é publico, ver config.php)
        $this->model = $this->model('Usuario');
        $this->helper("crud");                 // mensagens()
    }

    /**
     * index
     * Exibe o formulario de cadastro.
     *
     * @return void
     */
    public function index()
    {
        $this->view("cadastro");

        // formErrors/formInputs ja foram usados pela view (campoErro/valorAntigo);
        // limpa agora para nao reaparecer numa proxima visita sem novo submit.
        Session::destroy('formErrors');
        Session::destroy('formInputs');
    }

    /**
     * salvar
     * Processa o POST do formulario de cadastro.
     *
     * @return void
     */
    public function salvar()
    {
        $dados = $this->request->getPost();

        // 'data' no formulario -> 'data_nascimento' no banco
        $dados['data_nascimento'] = $dados['data'] ?? null;

        // RN de cadastro que o Validator declarativo (nome/email/senha) nao cobre:
        // confirmacao de senha e aceite dos termos precisam de checagem manual.
        $errosExtras = [];

        if (($dados['senha'] ?? '') !== ($dados['confirmar_senha'] ?? '')) {
            $errosExtras['confirmar_senha'] = 'As senhas informadas não conferem.';
        }

        if (empty($dados['aceite_termos'])) {
            $errosExtras['aceite_termos'] = 'É preciso aceitar os Termos de Uso/Política de Privacidade.';
        }

        if (!empty($dados['email']) && $this->model->emailJaExiste($dados['email'])) {
            $errosExtras['email'] = 'Já existe uma conta cadastrada com este e-mail.';
        }

        // validate() roda as regras declarativas (nome/email/senha) e ja
        // guarda os erros na sessao (formErrors/formInputs) se houver algum.
        $valido = $this->model->validate($dados);

        if (!empty($errosExtras)) {
            // Mescla com o que o validate() ja tiver guardado, sem perder nada.
            $erros = array_merge(Session::get('formErrors') ?: [], $errosExtras);
            Session::set('formErrors', $erros);
            Session::set('formInputs', $dados);
            $valido = false;
        }

        if (!$valido) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header("Location: /Cadastro");
        }

        $novoId = $this->model->insert($dados);

        if ($novoId > 0) {
            Session::set('msgSucesso', 'Cadastro realizado com sucesso! Faca login para continuar.');
            return header("Location: /Login");
        }

        Session::set('msgError', 'Nao foi possivel concluir o cadastro. Tente novamente.');
        return header("Location: /Cadastro");
    }
}
