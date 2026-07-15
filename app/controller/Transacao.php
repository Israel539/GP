<?php

namespace App\Controller;

use App\Library\Session;

class Transacao extends BaseController
{
    protected $model;
    protected $contaModel;
    protected $categoriaModel;
    protected $tagModel;
    protected $cartaoModel;

    public function __construct()
    {
        parent::__construct();
        $this->model          = $this->model('Transacao');
        $this->contaModel     = $this->model('Conta');
        $this->categoriaModel = $this->model('Categoria');
        $this->tagModel       = $this->model('Tag');
        $this->cartaoModel    = $this->model('CartaoCredito');
        $this->helper("crud");
    }

    /**
     * extrato
     * URL: /Transacao/extrato/{contaId}?data_inicio=&data_fim=&categoria_id=
     *
     * @return void
     */
    public function extrato()
    {
        $contaId = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();
        if (!$this->podeVisualizarConta($contaId, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $filtros = array_filter([
            'data_inicio'  => $_GET['data_inicio'] ?? null,
            'data_fim'     => $_GET['data_fim'] ?? null,
            'categoria_id' => $_GET['categoria_id'] ?? null,
        ]);

        $conta       = $this->contaModel->buscarPorId($contaId);
        $transacoes  = $this->model->listarPorConta($contaId, $filtros);
        $saldoAtual  = $this->contaModel->saldoAtual($contaId);
        $categorias  = $this->categoriaModel->listarDisponiveis((int) $conta['usuario_id']);
        $cartoes     = $this->cartaoModel->listarPorUsuario((int) $conta['usuario_id']);
        $tags        = $this->tagModel->listarPorUsuario((int) $conta['usuario_id']);

        // Tags ja vinculadas a cada transacao, para exibir/editar na linha.
        foreach ($transacoes as &$t) {
            $t['tags'] = $this->tagModel->listarPorTransacao((int) $t['id']);
        }

        return $this->view("extrato", [
            'conta'       => $conta,
            'transacoes'  => $transacoes,
            'saldoAtual'  => $saldoAtual,
            'categorias'  => $categorias,
            'cartoes'     => $cartoes,
            'tags'        => $tags,
            'filtros'     => $filtros,
        ]);
    }

    /**
     * lancar
     * Cria uma transacao manual (RN09 roteia credito para a fatura).
     *
     * @return void
     */
    public function lancar()
    {
        $post    = $this->request->getPost();
        $contaId = (int) ($post['conta_id'] ?? 0);
        $usuario = $this->usuarioLogado();
        if (!$this->podeGerenciarConta($contaId, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        if (!$this->model->validate($post)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header("Location: /Transacao/extrato/{$contaId}");
        }

        if (($post['modalidade'] ?? '') === 'credito' && empty($post['cartao_id'])) {
            Session::set('msgError', 'Selecione o cartao para uma transacao de credito.');
            return header("Location: /Transacao/extrato/{$contaId}");
        }

        try {
            $transacaoId = $this->model->criarManual([
                'conta_id'          => $contaId,
                'categoria_id'      => !empty($post['categoria_id']) ? (int) $post['categoria_id'] : null,
                'cartao_id'         => !empty($post['cartao_id']) ? (int) $post['cartao_id'] : null,
                'descricao'         => $post['descricao'],
                'valor'             => abs((float) str_replace(',', '.', $post['valor'])),
                'tipo'              => $post['tipo'],
                'modalidade'        => $post['modalidade'] ?? 'outro',
                'data_fato_gerador' => $post['data_fato_gerador'],
                'data_competencia'  => $post['data_competencia'] ?? $post['data_fato_gerador'],
            ]);
        } catch (\InvalidArgumentException $ex) {
            Session::set('msgError', $ex->getMessage());
            return header("Location: /Transacao/extrato/{$contaId}");
        }

        $this->vincularTagsDoTexto($transacaoId, (int) $usuario['id'], $post['tags'] ?? '');

        Session::set('msgSucesso', 'Transacao lancada.');
        return header("Location: /Transacao/extrato/{$contaId}");
    }

    /**
     * atualizarCategoria
     * URL: /Transacao/atualizarCategoria/{id}
     * RN07: se a transacao for de origem api_openfinance, o proprio
     * TransacaoModel::atualizar() ja recusa qualquer campo alem de
     * categoria_id -- aqui so repassamos o que veio do form.
     *
     * @return void
     */
    public function atualizarCategoria()
    {
        $transacaoId = (int) $this->request->getAction();
        $transacao   = $this->model->buscarPorId($transacaoId);

        if (count($transacao) === 0) {
            Session::set('msgError', 'Transacao nao encontrada.');
            return header("Location: /Conta");
        }

        $contaId = (int) $transacao['conta_id'];
        $usuario = $this->usuarioLogado();
        if (!$this->podeGerenciarConta($contaId, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $post = $this->request->getPost();

        $this->model->atualizar($transacaoId, [
            'categoria_id' => !empty($post['categoria_id']) ? (int) $post['categoria_id'] : null,
        ]);

        // Tags sempre editaveis, mesmo em transacao importada (RN07 libera
        // categorias e tags). Substitui o conjunto atual pelo informado.
        foreach ($this->tagModel->listarPorTransacao($transacaoId) as $tagAtual) {
            $this->tagModel->desvincular($transacaoId, (int) $tagAtual['id']);
        }
        $this->vincularTagsDoTexto($transacaoId, (int) $usuario['id'], $post['tags'] ?? '');

        Session::set('msgSucesso', 'Categoria/tags atualizadas.');
        return header("Location: /Transacao/extrato/{$contaId}");
    }

    /**
     * excluir
     * URL: /Transacao/excluir/{id}
     * RN07: TransacaoModel::excluir() ja recusa se origem = api_openfinance.
     *
     * @return void
     */
    public function excluir()
    {
        $transacaoId = (int) $this->request->getAction();
        $transacao   = $this->model->buscarPorId($transacaoId);

        if (count($transacao) === 0) {
            Session::set('msgError', 'Transacao nao encontrada.');
            return header("Location: /Conta");
        }

        $contaId = (int) $transacao['conta_id'];
        $usuario = $this->usuarioLogado();
        if (!$this->podeGerenciarConta($contaId, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $ok = $this->model->excluir($transacaoId);

        if ($ok) {
            Session::set('msgSucesso', 'Transacao excluida.');
        } else {
            Session::set('msgError', 'Transacoes importadas via Open Finance nao podem ser excluidas (RN07).');
        }

        return header("Location: /Transacao/extrato/{$contaId}");
    }

    /**
     * vincularTagsDoTexto
     * Recebe uma string "casa, mercado, viagem" vinda de um unico input de
     * texto, separa por virgula e vincula (criando a tag se nao existir).
     *
     * @param int $transacaoId
     * @param int $usuarioId
     * @param string $textoTags
     * @return void
     */
    protected function vincularTagsDoTexto(int $transacaoId, int $usuarioId, string $textoTags): void
    {
        $nomes = array_filter(array_map('trim', explode(',', $textoTags)));

        foreach ($nomes as $nome) {
            $tagId = $this->tagModel->buscarOuCriar($nome, $usuarioId);
            $this->tagModel->vincularATransacao($transacaoId, $tagId);
        }
    }

    /**
     * podeVisualizarConta
     * Ver o extrato: dono da conta, OU um admin com concessao de suporte
     * ATIVA e ESPECIFICA para esta conta (ver Admin::suporteAcessar()).
     *
     * @param int $contaId
     * @param int $usuarioId
     * @return bool
     */
    protected function podeVisualizarConta(int $contaId, int $usuarioId): bool
    {
        return $this->contaModel->usuarioEhDono($contaId, $usuarioId)
            || $this->temAcessoSuporteAtivo('conta', $contaId);
    }

    /**
     * podeGerenciarConta
     * Lancar, editar categoria ou excluir transacao sao ACOES -- exigem ser
     * o dono de verdade. Acesso de suporte nunca da bypass aqui, so serve
     * para inspecionar (ver podeVisualizarConta acima).
     *
     * @param int $contaId
     * @param int $usuarioId
     * @return bool
     */
    protected function podeGerenciarConta(int $contaId, int $usuarioId): bool
    {
        return $this->contaModel->usuarioEhDono($contaId, $usuarioId);
    }

    /**
     * negarAcesso
     *
     * @return void
     */
    protected function negarAcesso()
    {
        http_response_code(403);
        Session::set('msgError', 'Voce nao tem acesso a esta conta.');
        return header("Location: /Conta");
    }
}
