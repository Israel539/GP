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
            return $this->view('contatoUsuario', ['contatos' => $contatos, 'usuario' => $usuario]);
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
}
