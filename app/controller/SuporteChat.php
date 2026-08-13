<?php

namespace App\Controller;

use App\Library\Session;

class SuporteChat extends BaseController
{
    protected $logModel;
    protected $mensagemModel;

    public function __construct()
    {
        parent::__construct();
        $this->logModel      = $this->model('LogAcessoSuporte');
        $this->mensagemModel = $this->model('MensagemSuporte');
    }

    /**
     * status
     * URL: /SuporteChat/status (GET, JSON)
     * Descobre se o usuario logado (admin OU usuario comum) esta
     * participando de uma sessao de suporte ativa agora -- e essa checagem,
     * chamada em toda pagina, que faz a caixinha de chat aparecer/sumir
     * sozinha pros dois lados.
     *
     * @return void
     */
    public function status()
    {
        $usuario = $this->usuarioLogado();
        $log     = $this->sessaoAtivaParaUsuario((int) $usuario['id']);

        if ($log === null) {
            return $this->json(['ativo' => false]);
        }

        $ehAdmin = (int) $log['admin_id'] === (int) $usuario['id'];

        return $this->json([
            'ativo'        => true,
            'log_id'       => (int) $log['id'],
            'papel'        => $ehAdmin ? 'admin' : 'usuario',
            'outro_nome'   => $ehAdmin ? $log['alvo_nome'] : $log['admin_nome'],
            'recurso'      => $log['tipo_recurso'],
            'expira_em_ts' => strtotime($log['expira_em']),
        ]);
    }

    /**
     * mensagens
     * URL: /SuporteChat/mensagens/{logId}?desde={ultimoId} (GET, JSON)
     * Polling incremental: so devolve mensagens mais novas que 'desde'.
     *
     * @return void
     */
    public function mensagens()
    {
        $logId   = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();
        $log     = $this->logModel->buscarPorId($logId);

        if (!$this->participaDaSessao($log, (int) $usuario['id'])) {
            http_response_code(403);
            return $this->json(['erro' => 'Sem acesso a essa conversa.']);
        }

        $desde     = (int) ($this->request->getQuery('desde') ?? 0);
        $mensagens = $this->mensagemModel->listarNovasDesde($logId, $desde);

        return $this->json(['mensagens' => $mensagens]);
    }

    /**
     * enviar
     * URL: /SuporteChat/enviar/{logId} (POST, JSON)
     *
     * @return void
     */
    public function enviar()
    {
        $logId   = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();
        $log     = $this->logModel->buscarPorId($logId);

        if (!$this->participaDaSessao($log, (int) $usuario['id']) || !$this->logModel->estaAtiva($log)) {
            http_response_code(403);
            return $this->json(['ok' => false, 'erro' => 'Essa conversa nao esta mais ativa.']);
        }

        $post  = $this->request->getPost();
        $texto = trim($post['mensagem'] ?? '');

        if ($texto === '') {
            return $this->json(['ok' => false, 'erro' => 'Mensagem vazia.']);
        }

        if (mb_strlen($texto) > 2000) {
            return $this->json(['ok' => false, 'erro' => 'Mensagem muito longa (maximo 2000 caracteres).']);
        }

        $id        = $this->mensagemModel->enviar($logId, (int) $usuario['id'], $texto);
        $mensagem  = $this->mensagemModel->buscarPorId($id);

        return $this->json(['ok' => true, 'mensagem' => $mensagem]);
    }

    /**
     * encerrar
     * URL: /SuporteChat/encerrar/{logId} (POST, JSON)
     * Encerra a sessao de suporte antes do prazo de 15min (botao "Encerrar
     * suporte" no chat) -- tanto o admin quanto o usuario alvo podem
     * encerrar. A caixinha some para os dois lados no proximo poll de
     * status().
     *
     * @return void
     */
    public function encerrar()
    {
        $logId   = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();
        $log     = $this->logModel->buscarPorId($logId);

        if (!$this->participaDaSessao($log, (int) $usuario['id'])) {
            http_response_code(403);
            return $this->json(['ok' => false, 'erro' => 'Sem acesso a essa conversa.']);
        }

        $this->logModel->encerrar($logId, (int) $usuario['id']);

        // Se quem encerrou foi o proprio admin, tambem derruba a janela de
        // acesso privilegiado dele na hora (nao so o chat) -- nao faria
        // sentido "encerrar o suporte" e o admin continuar navegando no
        // recurso privado do usuario pelos minutos restantes.
        $concessao = Session::get('acessoSuporte');
        if (is_array($concessao) && (int) $log['admin_id'] === (int) $usuario['id']) {
            Session::destroy('acessoSuporte');
        }

        return $this->json(['ok' => true]);
    }

    /**
     * sessaoAtivaParaUsuario
     * Resolve se o usuario logado tem uma sessao de suporte ativa agora,
     * seja como ADMIN (concessao ja guardada na sessao PHP dele, sem
     * precisar de query no banco) ou como USUARIO ALVO (precisa de query,
     * ja que ele nao tem nada disso na propria sessao -- foi o admin que
     * iniciou o acesso).
     *
     * @param int $usuarioId
     * @return array|null
     */
    protected function sessaoAtivaParaUsuario(int $usuarioId): ?array
    {
        $concessao = Session::get('acessoSuporte');

        if (is_array($concessao) && !empty($concessao['id'])) {
            $log = $this->logModel->buscarPorId((int) $concessao['id']);
            if ($this->logModel->estaAtiva($log)) {
                return $log;
            }
        }

        $log = $this->logModel->buscarSessaoAtivaParaUsuario($usuarioId);

        return !empty($log) ? $log : null;
    }

    /**
     * participaDaSessao
     *
     * @param array $log
     * @param int $usuarioId
     * @return bool
     */
    protected function participaDaSessao(array $log, int $usuarioId): bool
    {
        if (empty($log)) {
            return false;
        }

        return (int) $log['admin_id'] === $usuarioId || (int) $log['usuario_alvo_id'] === $usuarioId;
    }

    /**
     * json
     * Atalho pra devolver uma resposta JSON e encerrar a execucao.
     *
     * @param array $dados
     * @return void
     */
    protected function json(array $dados)
    {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($dados);
        exit;
    }
}
