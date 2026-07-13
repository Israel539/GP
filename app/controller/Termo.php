<?php

namespace App\Controller;

use App\Library\Session;

class Termo extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $usuario = $this->usuarioLogado();
        $termoModel = $this->model('Termo');
        $termos = $termoModel->buscarAtivosNaoAceitos((int) $usuario['id']);

        if (empty($termos)) {
            return header('Location: /Projeto');
        }

        return $this->view('termoAceite', ['termos' => $termos]);
    }

    public function aceitar()
    {
        $usuario = $this->usuarioLogado();
        $post = $this->request->getPost();
        $termoIds = $post['termo_ids'] ?? [];

        if (!is_array($termoIds) || empty($termoIds)) {
            Session::set('msgError', 'É preciso aceitar os termos para continuar.');
            return header('Location: /Termo');
        }

        $termoModel = $this->model('Termo');
        $termosPendentes = $termoModel->buscarAtivosNaoAceitos((int) $usuario['id']);
        $idsPendentes = array_map('intval', array_column($termosPendentes, 'id'));
        $idsRecebidos = array_map('intval', $termoIds);

        if (array_diff($idsPendentes, $idsRecebidos)) {
            Session::set('msgError', 'Você deve marcar todos os termos pendentes para continuar.');
            return header('Location: /Termo');
        }

        $aceitos = $termoModel->aceitarTermos(
            (int) $usuario['id'],
            $idsRecebidos,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );

        if ($aceitos > 0) {
            Session::set('msgSucesso', 'Aceite registrado com sucesso.');
            return header('Location: /Projeto');
        }

        Session::set('msgError', 'Não foi possível registrar o aceite. Tente novamente.');
        return header('Location: /Termo');
    }
}
