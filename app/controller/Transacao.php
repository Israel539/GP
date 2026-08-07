<?php

namespace App\Controller;

use App\Library\Session;

class Transacao extends BaseController
{
    /** @var \App\Model\TransacaoModel */
    protected $model;

    /** @var \App\Model\ContaModel */
    protected $contaModel;

    /** @var \App\Model\CategoriaModel */
    protected $categoriaModel;

    /** @var \App\Model\TagModel */
    protected $tagModel;

    /** @var \App\Model\CartaoCreditoModel */
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
     * exportarCsv
     * URL: /Transacao/exportarCsv/{contaId}?data_inicio=&data_fim=&categoria_id=
     * Mesmos filtros da tela de extrato -- os botoes de exportar reusam o
     * proprio formulario de filtro (formaction), entao o periodo exportado
     * e sempre o que esta sendo visualizado no momento.
     *
     * @return void
     */
    public function exportarCsv()
    {
        $contaId = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();
        if (!$this->podeVisualizarConta($contaId, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        [$conta, $transacoes, $filtros, $totais] = $this->dadosParaExportacao($contaId);

        $nomeArquivo = $this->nomeArquivoExportacao($conta['nome'], 'csv');

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        $saida = fopen('php://output', 'w');

        // BOM UTF-8 -- sem isso o Excel no Windows abre acentuacao errada.
        fwrite($saida, "\xEF\xBB\xBF");

        // Ponto e virgula como separador (nao virgula): e o padrao que o
        // Excel em pt-BR espera, porque virgula ja e usada como separador
        // decimal nos numeros.
        fputcsv($saida, ['Data', 'Descricao', 'Categoria', 'Tipo', 'Modalidade', 'Valor (R$)', 'Origem'], ';');

        foreach ($transacoes as $t) {
            fputcsv($saida, [
                $t['data_fato_gerador'],
                $t['descricao'],
                $t['categoria_nome'] ?? '(sem categoria)',
                ucfirst($t['tipo']),
                ucfirst($t['modalidade']),
                number_format((float) $t['valor'], 2, ',', ''),
                $t['origem'] === 'api_openfinance' ? 'Open Finance' : 'Manual',
            ], ';');
        }

        fputcsv($saida, [], ';');
        fputcsv($saida, ['', '', '', '', 'Total receitas', number_format($totais['receitas'], 2, ',', '')], ';');
        fputcsv($saida, ['', '', '', '', 'Total despesas', number_format($totais['despesas'], 2, ',', '')], ';');
        fputcsv($saida, ['', '', '', '', 'Saldo do periodo', number_format($totais['receitas'] - $totais['despesas'], 2, ',', '')], ';');

        fclose($saida);
        exit;
    }

    /**
     * exportarPdf
     * URL: /Transacao/exportarPdf/{contaId}?data_inicio=&data_fim=&categoria_id=
     *
     * @return void
     */
    public function exportarPdf()
    {
        $contaId = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();
        if (!$this->podeVisualizarConta($contaId, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        [$conta, $transacoes, $filtros, $totais] = $this->dadosParaExportacao($contaId);

        $categoriaFiltroNome = null;
        if (!empty($filtros['categoria_id'])) {
            foreach ($this->categoriaModel->listarDisponiveis((int) $conta['usuario_id']) as $cat) {
                if ((int) $cat['id'] === (int) $filtros['categoria_id']) {
                    $categoriaFiltroNome = $cat['nome'];
                    break;
                }
            }
        }

        ob_start();
        require __DIR__ . '/../view/extratoPdf.php';
        $html = ob_get_clean();

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans'); // fonte com suporte a acentuacao

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $nomeArquivo = $this->nomeArquivoExportacao($conta['nome'], 'pdf');

        $dompdf->stream($nomeArquivo, ['Attachment' => true]);
        exit;
    }

    /**
     * dadosParaExportacao
     * Busca os mesmos dados que a tela de extrato usa, mais os totais do
     * periodo -- compartilhado entre exportarCsv() e exportarPdf() pra nao
     * duplicar a query e o calculo de totais nos dois métodos.
     *
     * @param int $contaId
     * @return array [$conta, $transacoes, $filtros, $totais]
     */
    protected function dadosParaExportacao(int $contaId): array
    {
        $filtros = array_filter([
            'data_inicio'  => $_GET['data_inicio'] ?? null,
            'data_fim'     => $_GET['data_fim'] ?? null,
            'categoria_id' => $_GET['categoria_id'] ?? null,
        ]);

        $conta      = $this->contaModel->buscarPorId($contaId);
        $transacoes = $this->model->listarPorConta($contaId, $filtros);

        $totais = ['receitas' => 0.0, 'despesas' => 0.0];
        foreach ($transacoes as $t) {
            if ($t['tipo'] === 'receita') {
                $totais['receitas'] += (float) $t['valor'];
            } else {
                $totais['despesas'] += (float) $t['valor'];
            }
        }

        return [$conta, $transacoes, $filtros, $totais];
    }

    /**
     * nomeArquivoExportacao
     * Monta um nome de arquivo seguro (sem acento/espaco/caractere especial)
     * pro download, ex: extrato_conta-corrente_20260807_1432.csv
     *
     * @param string $nomeConta
     * @param string $extensao 'csv' ou 'pdf'
     * @return string
     */
    protected function nomeArquivoExportacao(string $nomeConta, string $extensao): string
    {
        $semAcento = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nomeConta) ?: $nomeConta;
        $slug      = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $semAcento), '-'));

        return "extrato_{$slug}_" . date('Ymd_Hi') . ".{$extensao}";
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
     * editar
     * URL: /Transacao/editar/{id}
     * Mostra o formulario de edicao para uma transacao manual.
     *
     * @return void
     */
    public function editar()
    {
        $transacaoId = (int) $this->request->getAction();
        $transacao   = $this->model->buscarPorId($transacaoId);

        if (count($transacao) === 0) {
            Session::set('msgError', 'Transacao nao encontrada.');
            return header('Location: /Conta');
        }

        if ($transacao['origem'] !== 'manual') {
            Session::set('msgError', 'Somente transacoes manuais podem ser editadas.');
            return header("Location: /Transacao/extrato/{$transacao['conta_id']}");
        }

        $contaId = (int) $transacao['conta_id'];
        $usuario = $this->usuarioLogado();
        if (!$this->podeGerenciarConta($contaId, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $conta      = $this->contaModel->buscarPorId($contaId);
        $categorias = $this->categoriaModel->listarDisponiveis((int) $conta['usuario_id']);
        $tags       = $this->tagModel->listarPorTransacao($transacaoId);
        $cartao     = null;

        if (!empty($transacao['cartao_id'])) {
            $cartao = $this->cartaoModel->buscarPorId((int) $transacao['cartao_id']);
        }

        return $this->view('transacaoEditar', [
            'conta'       => $conta,
            'transacao'   => $transacao,
            'categorias'  => $categorias,
            'tags'        => $tags,
            'cartao'      => $cartao,
        ]);
    }

    /**
     * atualizar
     * URL: /Transacao/atualizar/{id}
     * Atualiza uma transacao manual ja criada.
     *
     * @return void
     */
    public function atualizar()
    {
        $transacaoId = (int) $this->request->getAction();
        $transacao   = $this->model->buscarPorId($transacaoId);

        if (count($transacao) === 0) {
            Session::set('msgError', 'Transacao nao encontrada.');
            return header('Location: /Conta');
        }

        if ($transacao['origem'] !== 'manual') {
            Session::set('msgError', 'Somente transacoes manuais podem ser editadas.');
            return header("Location: /Transacao/extrato/{$transacao['conta_id']}");
        }

        $contaId = (int) $transacao['conta_id'];
        $usuario = $this->usuarioLogado();
        if (!$this->podeGerenciarConta($contaId, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $post = $this->request->getPost();

        $dados = [
            'descricao'         => $post['descricao'] ?? '',
            'valor'             => abs((float) str_replace(',', '.', $post['valor'] ?? '0')),
            'tipo'              => $post['tipo'] ?? $transacao['tipo'],
            'data_fato_gerador' => $post['data_fato_gerador'] ?? $transacao['data_fato_gerador'],
            'data_competencia'  => $post['data_competencia'] ?? $post['data_fato_gerador'] ?? $transacao['data_competencia'],
            'categoria_id'      => !empty($post['categoria_id']) ? (int) $post['categoria_id'] : null,
        ];

        if ($transacao['modalidade'] === 'credito') {
            $dados['modalidade'] = 'credito';
        } else {
            $dados['modalidade'] = $post['modalidade'] ?? $transacao['modalidade'];
        }

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header("Location: /Transacao/editar/{$transacaoId}");
        }

        try {
            $this->model->atualizar($transacaoId, $dados);
        } catch (\InvalidArgumentException $ex) {
            Session::set('msgError', $ex->getMessage());
            return header("Location: /Transacao/editar/{$transacaoId}");
        }

        foreach ($this->tagModel->listarPorTransacao($transacaoId) as $tagAtual) {
            $this->tagModel->desvincular($transacaoId, (int) $tagAtual['id']);
        }
        $this->vincularTagsDoTexto($transacaoId, (int) $usuario['id'], $post['tags'] ?? '');

        Session::set('msgSucesso', 'Transacao atualizada.');
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
