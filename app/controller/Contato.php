<?php

namespace App\Controller;

use App\Library\Session;
use App\Library\Mailer;

class Contato extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $usuario = $this->usuarioLogado();
        $contatoModel = $this->model('Contato');

        if (is_array($usuario)) {
            $contatos = $contatoModel->listarPorUsuario((int) $usuario['id']);
            $excluidosCount = $contatoModel->contarExcluidosPorUsuario((int) $usuario['id']);

            return $this->view('contatoUsuario', [
                'contatos' => $contatos,
                'usuario' => $usuario,
                'excluidosCount' => $excluidosCount,
            ]);
        }

        return $this->view('contatoPublico');
    }

    public function enviar()
    {
        $post = $this->request->getPost();
        $usuario = $this->usuarioLogado();
        $contatoModel = $this->model('Contato');

        $dados = [
            'usuario_id' => is_array($usuario) ? (int) $usuario['id'] : null,
            'nome'       => $post['nome'] ?? '',
            'email'      => $post['email'] ?? '',
            'assunto'    => $post['assunto'] ?? '',
            'mensagem'   => $post['mensagem'] ?? '',
        ];

        if (!$contatoModel->validate($dados)) {
            Session::set('msgError', 'Verifique os campos e tente novamente.');
            return header('Location: /Home/contato');
        }

        $contatoId = $contatoModel->enviar($dados);

        if ($contatoId === 0) {
            Session::set('msgError', 'Nao foi possivel enviar sua mensagem. Tente novamente.');
            return header('Location: /Home/contato');
        }

        $adminEmail = MAIL_CONF['MAIL_USER'];
        $linkAdmin = BASEURL . 'Admin/contatos';
        $corpo = "<p>Nova mensagem de contato:</p>"
               . "<p><strong>Nome:</strong> " . htmlspecialchars($dados['nome']) . "</p>"
               . "<p><strong>E-mail:</strong> " . htmlspecialchars($dados['email']) . "</p>"
               . "<p><strong>Assunto:</strong> " . htmlspecialchars($dados['assunto']) . "</p>"
               . "<p><strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($dados['mensagem'])) . "</p>"
               . "<p><a href=\"{$linkAdmin}\">Ver e responder no painel do admin</a></p>";

        Mailer::enviar($adminEmail, 'Administrador', 'Novo contato recebido', $corpo);

        Session::set('msgSucesso', 'Sua mensagem foi enviada. O administrador sera notificado.');
        return header('Location: /Home/contato');
    }

    public function limparHistorico()
    {
        $usuario = $this->usuarioLogado();

        if (!is_array($usuario)) {
            // somente usuarios logados podem limpar seu historico
            Session::set('msgError', 'Usuario nao autenticado.');
            return header('Location: /Home/contato');
        }

        $contatoModel = $this->model('Contato');
        $ok = $contatoModel->limparPorUsuario((int) $usuario['id']);

        if ($ok) {
            Session::set('msgSucesso', 'Historico de contato limpo com sucesso.');
        } else {
            Session::set('msgError', 'Nao foi possivel limpar o historico.');
        }

        return header('Location: /Contato');
    }

    public function restaurarHistorico()
    {
        $usuario = $this->usuarioLogado();

        if (!is_array($usuario)) {
            Session::set('msgError', 'Usuario nao autenticado.');
            return header('Location: /Home/contato');
        }

        $contatoModel = $this->model('Contato');
        $ok = $contatoModel->restaurarPorUsuario((int) $usuario['id']);

        if ($ok) {
            Session::set('msgSucesso', 'Historico restaurado com sucesso.');
        } else {
            Session::set('msgError', 'Nao foi possivel restaurar o historico.');
        }

        return header('Location: /Contato');
    }
}
