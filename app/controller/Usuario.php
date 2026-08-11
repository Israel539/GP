<?php

namespace App\Controller;

use App\Library\Session;
use App\Library\Validator;

class Usuario extends BaseController
{
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = $this->model('Usuario');
        $this->helper("crud");
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
     * editar
     * URL: /Usuario/editar
     * Formulario para editar os dados de perfil (nome, e-mail, CPF, data de
     * nascimento, WhatsApp, foto) e trocar a senha.
     *
     * @return void
     */
    public function editar()
    {
        $usuario = $this->usuarioLogado();

        if (!is_array($usuario) || empty($usuario['id'])) {
            Session::set('msgError', 'Para acessar seu perfil, faça login primeiro.');
            return header('Location: /Login');
        }

        $usuarioCompleto = $this->model->buscarPorId((int) $usuario['id']);
        $this->view('usuarioPerfilEditar', ['usuario' => $usuarioCompleto]);

        // formErrors/formInputs ja foram usados pela view (campoErro/valorAntigo);
        // limpa agora para nao reaparecer numa proxima visita sem novo submit.
        Session::destroy('formErrors');
        Session::destroy('formInputs');
    }

    /**
     * atualizar
     * URL: /Usuario/atualizar (POST)
     * Processa o formulario de edicao de perfil (dados cadastrais + foto).
     * Troca de senha tem endpoint proprio (alterarSenha()), de proposito --
     * sao duas acoes com regras de validacao bem diferentes.
     *
     * @return void
     */
    public function atualizar()
    {
        $usuario   = $this->usuarioLogado();
        $usuarioId = (int) $usuario['id'];
        $dados     = $this->request->getPost();

        // Regras proprias da edicao de perfil (nao reaproveita
        // UsuarioModel::$validationRules porque la 'senha' e obrigatoria --
        // faz sentido no cadastro, mas nao aqui, onde a senha tem fluxo
        // separado).
        $regras = [
            'nome'  => ['rules' => 'required|min:3|max:120', 'label' => 'Nome'],
            'email' => ['rules' => 'required|email|max:150', 'label' => 'E-mail'],
        ];

        $temErro = Validator::make($dados, $regras);

        if (!$temErro && $this->model->emailJaExisteEmOutraConta($dados['email'], $usuarioId)) {
            Session::set('formErrors', ['email' => 'Já existe outra conta cadastrada com este e-mail.']);
            Session::set('formInputs', $dados);
            $temErro = true;
        }

        if ($temErro) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header('Location: /Usuario/editar');
        }

        $dadosParaSalvar = [
            'nome'            => $dados['nome'],
            'email'           => $dados['email'],
            'cpf'             => $dados['cpf'] ?? '',
            'data_nascimento' => $dados['data_nascimento'] ?? '',
            'telefone_whats'  => $dados['telefone_whats'] ?? '',
        ];

        $novaFotoUrl = $this->processarUploadFoto($usuarioId);

        if ($novaFotoUrl !== null) {
            $dadosParaSalvar['foto'] = $novaFotoUrl;
        } elseif (!empty($dados['remover_foto'])) {
            $this->removerArquivoFotoAtual($usuarioId);
            $dadosParaSalvar['foto'] = '';
        }

        $this->model->atualizar($usuarioId, $dadosParaSalvar);

        // Mantem a sessao coerente com o que acabou de ser salvo, sem
        // exigir logout/login pra o nome novo aparecer no menu, por exemplo.
        Session::set(SESSION_USER_KEY, array_merge($usuario, [
            'nome'  => $dados['nome'],
            'email' => $dados['email'],
        ]));

        Session::set('msgSucesso', 'Perfil atualizado com sucesso.');
        return header('Location: /Usuario/perfil');
    }

    /**
     * alterarSenha
     * URL: /Usuario/alterarSenha (POST)
     * Exige a senha atual antes de trocar -- evita que alguem que pegue a
     * sessao aberta (ex: computador destravado) troque a senha sem saber a
     * atual e tranque o dono de fora da propria conta.
     *
     * @return void
     */
    public function alterarSenha()
    {
        $usuario   = $this->usuarioLogado();
        $usuarioId = (int) $usuario['id'];
        $dados     = $this->request->getPost();

        $senhaAtual     = $dados['senha_atual'] ?? '';
        $novaSenha      = $dados['nova_senha'] ?? '';
        $confirmarSenha = $dados['confirmar_nova_senha'] ?? '';
        $hashAtual      = $this->model->buscarSenhaHash($usuarioId);

        $erros = [];

        if ($hashAtual === null || !password_verify($senhaAtual, $hashAtual)) {
            $erros['senha_atual'] = 'Senha atual incorreta.';
        }

        if (strlen($novaSenha) < 6) {
            $erros['nova_senha'] = 'A nova senha precisa ter no mínimo 6 caracteres.';
        }

        if ($novaSenha !== $confirmarSenha) {
            $erros['confirmar_nova_senha'] = 'As senhas informadas não conferem.';
        }

        if (!empty($erros)) {
            Session::set('formErrors', $erros);
            Session::set('msgError', 'Nao foi possivel trocar a senha. Verifique os campos destacados.');
            return header('Location: /Usuario/editar');
        }

        $this->model->redefinirSenha($usuarioId, $novaSenha);

        Session::set('msgSucesso', 'Senha alterada com sucesso.');
        return header('Location: /Usuario/editar');
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

    /**
     * processarUploadFoto
     * Mesmo padrao de PlanoCompra::processarUploadImagem() (getimagesize()
     * confere o CONTEUDO do arquivo, nao so a extensao). Devolve null se
     * nao veio nenhum arquivo novo -- nesse caso o Controller mantem a foto
     * que a pessoa ja tinha (ou processa 'remover_foto', se marcado).
     *
     * @param int $usuarioId
     * @return string|null
     */
    protected function processarUploadFoto(int $usuarioId): ?string
    {
        if (empty($_FILES['foto_arquivo']) || $_FILES['foto_arquivo']['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $arquivo = $_FILES['foto_arquivo'];

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            Session::set('msgError', 'Falha ao enviar a foto. Tente novamente.');
            return null;
        }

        if ($arquivo['size'] > 5 * 1024 * 1024) {
            Session::set('msgError', 'A foto precisa ter no maximo 5MB.');
            return null;
        }

        $info = @getimagesize($arquivo['tmp_name']);
        $extensoesPermitidas = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if ($info === false || !isset($extensoesPermitidas[$info['mime']])) {
            Session::set('msgError', 'Envie uma imagem valida (JPG, PNG ou WEBP).');
            return null;
        }

        $this->removerArquivoFotoAtual($usuarioId);

        $nomeArquivo  = 'perfil_' . $usuarioId . '_' . uniqid() . '.' . $extensoesPermitidas[$info['mime']];
        $pastaDestino = __DIR__ . '/../../public/uploads/perfil';

        if (!is_dir($pastaDestino)) {
            @mkdir($pastaDestino, 0775, true);
        }

        $destino = $pastaDestino . '/' . $nomeArquivo;

        if (!@move_uploaded_file($arquivo['tmp_name'], $destino)) {
            Session::set('msgError', 'Nao foi possivel salvar a foto no servidor.');
            return null;
        }

        return BASEURL . 'uploads/perfil/' . $nomeArquivo;
    }

    /**
     * removerArquivoFotoAtual
     * Apaga o arquivo fisico da foto anterior do usuario (se houver e se
     * estiver dentro da pasta de uploads de perfil -- confere o caminho de
     * proposito, pra nunca apagar nada fora dali por engano).
     *
     * @param int $usuarioId
     * @return void
     */
    protected function removerArquivoFotoAtual(int $usuarioId): void
    {
        $usuarioAtual = $this->model->buscarPorId($usuarioId);
        $fotoAtual    = $usuarioAtual['foto'] ?? null;

        if (empty($fotoAtual)) {
            return;
        }

        $pastaUploads = realpath(__DIR__ . '/../../public/uploads/perfil');
        $nomeArquivo  = basename($fotoAtual);
        $caminhoFull  = $pastaUploads . '/' . $nomeArquivo;

        if ($pastaUploads !== false && is_file($caminhoFull)) {
            @unlink($caminhoFull);
        }
    }
}
