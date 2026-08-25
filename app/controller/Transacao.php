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

        $periodo = $this->periodoAtual();

        $filtros = array_filter([
            'data_inicio'  => $periodo['data_inicio'],
            'data_fim'     => $periodo['data_fim'],
            'categoria_id' => $_GET['categoria_id'] ?? null,
        ]);

        $conta       = $this->contaModel->buscarPorId($contaId);
        $transacoes  = $this->model->listarPorConta($contaId, $filtros);
        $saldoAtual  = $this->contaModel->saldoAtual($contaId);
        $categorias  = $this->categoriaModel->listarDisponiveis((int) $conta['usuario_id']);
        $cartoes     = $this->cartaoModel->listarPorUsuario((int) $conta['usuario_id']);
        $tags        = $this->tagModel->listarPorUsuario((int) $conta['usuario_id']);
        $lixeira     = $this->model->listarExcluidasRecentes($contaId);

        $totaisFiltro = ['receitas' => 0.0, 'despesas' => 0.0];
        foreach ($transacoes as $t) {
            $totaisFiltro[$t['tipo'] === 'receita' ? 'receitas' : 'despesas'] += (float) $t['valor'];
        }

        // Widget opcional de dinheiro fisico + total (ver migracao 012) --
        // a sessao so tem id/nome/email/nivel, entao busca o usuario
        // completo so aqui, que e onde os campos extras sao usados.
        $usuarioCompleto = $this->model('Usuario')->buscarPorId((int) $usuario['id']);

        // Tags ja vinculadas a cada transacao, para exibir/editar na linha.
        foreach ($transacoes as &$t) {
            $t['tags'] = $this->tagModel->listarPorTransacao((int) $t['id']);
        }

        return $this->view("extrato", [
            'conta'       => $conta,
            'transacoes'  => $transacoes,
            'saldoAtual'  => $saldoAtual,
            'totaisFiltro' => $totaisFiltro,
            'categorias'  => $categorias,
            'cartoes'     => $cartoes,
            'tags'        => $tags,
            'filtros'     => $filtros,
            'periodo'     => $periodo,
            'lixeira'     => $lixeira,
            'usuario'     => $usuarioCompleto,
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
                $this->sanitizarCelulaCsv($t['descricao']),
                $this->sanitizarCelulaCsv($t['categoria_nome'] ?? '(sem categoria)'),
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

        // RN08: saldo real da conta (view vw_saldo_contas), independente do
        // periodo/filtro que estiver sendo exportado -- e o que a pessoa
        // ve na tela do extrato, agora tambem no PDF.
        $saldoAtual = $this->contaModel->saldoAtual($contaId);

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
        $periodo = $this->periodoAtual();

        $filtros = array_filter([
            'data_inicio'  => $periodo['data_inicio'],
            'data_fim'     => $periodo['data_fim'],
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
     * periodoAtual
     * Resolve qual periodo esta ativo na tela de extrato (e nas
     * exportacoes, que reusam este mesmo metodo):
     *
     * - Por padrao, filtra pelo MES informado em ?mes=YYYY-MM (ou o mes
     *   atual, se nao vier nada) -- e o que faz o extrato nao virar uma
     *   lista infinita: so aparece o que e daquele mes, com navegacao
     *   pra frente/tras entre meses.
     * - Se vier ?data_inicio= e/ou ?data_fim= na URL, isso tem prioridade
     *   sobre o filtro por mes (fica como "periodo personalizado", pra
     *   quem quer um intervalo especifico que nao bate com um mes
     *   fechado).
     *
     * @return array
     */
    protected function periodoAtual(): array
    {
        $usandoIntervaloCustomizado = !empty($_GET['data_inicio']) || !empty($_GET['data_fim']);

        if ($usandoIntervaloCustomizado) {
            return [
                'mes'                          => date('Y-m'),
                'mes_anterior'                 => date('Y-m', strtotime('-1 month')),
                'mes_seguinte'                 => date('Y-m', strtotime('+1 month')),
                'mes_hoje'                     => date('Y-m'),
                'eh_mes_atual'                 => false,
                'rotulo'                       => 'Período personalizado',
                'usando_intervalo_customizado' => true,
                'data_inicio'                  => $_GET['data_inicio'] ?? null,
                'data_fim'                     => $_GET['data_fim'] ?? null,
            ];
        }

        $mesParam = (string) ($_GET['mes'] ?? '');
        $mes      = preg_match('/^\d{4}-\d{2}$/', $mesParam) ? $mesParam : date('Y-m');

        $inicioMes = $mes . '-01';
        $fimMes    = date('Y-m-t', strtotime($inicioMes));

        return [
            'mes'                          => $mes,
            'data_inicio'                  => $inicioMes,
            'data_fim'                     => $fimMes,
            'mes_anterior'                 => date('Y-m', strtotime($inicioMes . ' -1 month')),
            'mes_seguinte'                 => date('Y-m', strtotime($inicioMes . ' +1 month')),
            'mes_hoje'                     => date('Y-m'),
            'eh_mes_atual'                 => $mes === date('Y-m'),
            'rotulo'                       => $this->rotuloDoMes($mes),
            'usando_intervalo_customizado' => false,
        ];
    }

    /**
     * rotuloDoMes
     * "2026-08" -> "Agosto de 2026". Nao usa setlocale/strftime de
     * proposito -- em WAMP/Windows a locale pt_BR nem sempre esta instalada,
     * e isso quebraria o rotulo silenciosamente (viraria em ingles ou vazio)
     * dependendo do servidor.
     *
     * @param string $mes Formato YYYY-MM
     * @return string
     */
    protected function rotuloDoMes(string $mes): string
    {
        $nomesMeses = [
            '01' => 'Janeiro',   '02' => 'Fevereiro', '03' => 'Março',
            '04' => 'Abril',     '05' => 'Maio',      '06' => 'Junho',
            '07' => 'Julho',     '08' => 'Agosto',    '09' => 'Setembro',
            '10' => 'Outubro',   '11' => 'Novembro',  '12' => 'Dezembro',
        ];

        [$ano, $mesNum] = array_pad(explode('-', $mes), 2, '');

        return ($nomesMeses[$mesNum] ?? $mesNum) . ' de ' . $ano;
    }

    /**
     * sanitizarCelulaCsv
     * Protege contra "CSV Injection" (Formula Injection): se um valor
     * comecar com =, +, -, @ (ou tab/CR), programas como Excel podem
     * interpretar a celula como uma FORMULA em vez de texto quando o
     * arquivo e aberto -- isso pode rodar comandos no computador de quem
     * abrir. O texto vem de dados que nao sao 100% controlados pelo dono
     * da conta (ex: descricao de uma transacao importada via Open
     * Finance/Pix pode ter sido escrita por quem pagou, nao por quem
     * esta exportando). Prefixar com apostrofo faz o Excel/LibreOffice
     * tratar sempre como texto puro, nunca como formula.
     *
     * @param string $valor
     * @return string
     */
    protected function sanitizarCelulaCsv(string $valor): string
    {
        if ($valor !== '' && in_array($valor[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $valor;
        }

        return $valor;
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

        $numParcelas = (int) ($post['parcelas'] ?? 1);
        if ($numParcelas < 1 || $numParcelas > 24) {
            Session::set('msgError', 'Numero de parcelas invalido (use de 1 a 24).');
            return header("Location: /Transacao/extrato/{$contaId}");
        }

        $categoriaId = !empty($post['categoria_id']) ? (int) $post['categoria_id'] : null;

        // RN de seguranca: categoria (se informada) precisa ser do usuario
        // ou padrao do sistema -- sem isso, dava pra forjar categoria_id de
        // outro usuario no formulario e o nome dela aparecia no extrato via
        // JOIN (TransacaoModel usa LEFT JOIN categorias sem checar dono).
        if ($categoriaId !== null && !$this->categoriaModel->usuarioPodeUsar($categoriaId, (int) $usuario['id'])) {
            Session::set('msgError', 'Categoria invalida.');
            return header("Location: /Transacao/extrato/{$contaId}");
        }

        $dadosTransacao = [
            'conta_id'          => $contaId,
            'categoria_id'      => $categoriaId,
            'cartao_id'         => !empty($post['cartao_id']) ? (int) $post['cartao_id'] : null,
            'descricao'         => $post['descricao'],
            'valor'             => abs((float) str_replace(',', '.', $post['valor'])),
            'tipo'              => $post['tipo'],
            'modalidade'        => $post['modalidade'] ?? 'outro',
            'data_fato_gerador' => $post['data_fato_gerador'],
            'data_competencia'  => $post['data_competencia'] ?? $post['data_fato_gerador'],
        ];

        try {
            if ($numParcelas > 1) {
                // So faz sentido parcelar no credito -- validate() la na
                // model/JS ja empurra pra isso, mas confere de novo aqui
                // porque e o Model quem efetivamente grava.
                if (($dadosTransacao['modalidade'] ?? '') !== 'credito') {
                    Session::set('msgError', 'Parcelamento so e permitido para modalidade credito.');
                    return header("Location: /Transacao/extrato/{$contaId}");
                }

                $idsGerados = $this->model->criarParcelada($dadosTransacao, $numParcelas);
            } else {
                $idsGerados = [$this->model->criarManual($dadosTransacao)];
            }
        } catch (\InvalidArgumentException $ex) {
            Session::set('msgError', $ex->getMessage());
            return header("Location: /Transacao/extrato/{$contaId}");
        }

        // Marca a tag em TODAS as parcelas geradas -- senao filtrar por tag
        // so acharia a primeira parcela da compra, escondendo as outras.
        foreach ($idsGerados as $idGerado) {
            $this->vincularTagsDoTexto($idGerado, (int) $usuario['id'], $post['tags'] ?? '');
        }

        $mensagem = $numParcelas > 1 ? "Compra lancada em {$numParcelas}x." : 'Transacao lancada.';
        Session::set('msgSucesso', $mensagem);
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

        if (!empty($transacao['excluido_em'])) {
            Session::set('msgError', 'Essa transacao esta na lixeira. Restaure antes de editar.');
            return header("Location: /Transacao/extrato/{$transacao['conta_id']}");
        }

        $contaId = (int) $transacao['conta_id'];
        $usuario = $this->usuarioLogado();
        if (!$this->podeGerenciarConta($contaId, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $post = $this->request->getPost();

        $categoriaId = !empty($post['categoria_id']) ? (int) $post['categoria_id'] : null;

        // Mesma checagem de dono da categoria usada em lancar() -- ver o
        // comentario la pra entender o motivo.
        if ($categoriaId !== null && !$this->categoriaModel->usuarioPodeUsar($categoriaId, (int) $usuario['id'])) {
            Session::set('msgError', 'Categoria invalida.');
            return header("Location: /Transacao/extrato/{$contaId}");
        }

        $this->model->atualizar($transacaoId, [
            'categoria_id' => $categoriaId,
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

        if (!empty($transacao['excluido_em'])) {
            Session::set('msgError', 'Essa transacao esta na lixeira. Restaure antes de editar.');
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

        if (!empty($transacao['excluido_em'])) {
            Session::set('msgError', 'Essa transacao esta na lixeira. Restaure antes de editar.');
            return header("Location: /Transacao/extrato/{$transacao['conta_id']}");
        }

        $contaId = (int) $transacao['conta_id'];
        $usuario = $this->usuarioLogado();
        if (!$this->podeGerenciarConta($contaId, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $post = $this->request->getPost();

        $categoriaId = !empty($post['categoria_id']) ? (int) $post['categoria_id'] : null;

        // Mesma checagem de dono da categoria usada em lancar() -- ver o
        // comentario la pra entender o motivo.
        if ($categoriaId !== null && !$this->categoriaModel->usuarioPodeUsar($categoriaId, (int) $usuario['id'])) {
            Session::set('msgError', 'Categoria invalida.');
            return header("Location: /Transacao/editar/{$transacaoId}");
        }

        $dados = [
            'descricao'         => $post['descricao'] ?? '',
            'valor'             => abs((float) str_replace(',', '.', $post['valor'] ?? '0')),
            'tipo'              => $post['tipo'] ?? $transacao['tipo'],
            'data_fato_gerador' => $post['data_fato_gerador'] ?? $transacao['data_fato_gerador'],
            'data_competencia'  => $post['data_competencia'] ?? $post['data_fato_gerador'] ?? $transacao['data_competencia'],
            'categoria_id'      => $categoriaId,
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
     * Isso e um soft-delete -- a transacao vai pra lixeira e pode ser
     * restaurada em ate 1 dia (ver restaurar()).
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
            Session::set('msgSucesso', 'Transacao movida para a lixeira. Voce pode restaurar em ate 1 dia.');
        } elseif (!empty($transacao['excluido_em'])) {
            Session::set('msgError', 'Essa transacao ja estava na lixeira.');
        } else {
            Session::set('msgError', 'Transacoes importadas via Open Finance nao podem ser excluidas (RN07).');
        }

        return header("Location: /Transacao/extrato/{$contaId}");
    }

    /**
     * restaurar
     * URL: /Transacao/restaurar/{id}
     * Traz de volta uma transacao que esta na lixeira, desde que ainda
     * esteja dentro do prazo de 1 dia (TransacaoModel::restaurar() valida
     * isso e recusa se ja tiver passado).
     *
     * @return void
     */
    public function restaurar()
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

        $ok = $this->model->restaurar($transacaoId);

        if ($ok) {
            Session::set('msgSucesso', 'Transacao restaurada.');
        } else {
            Session::set('msgError', 'Nao foi possivel restaurar: o prazo de 1 dia ja passou, ou a transacao nao esta na lixeira.');
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
     * grupoParcela
     * URL: /Transacao/grupoParcela/{contaId}?grupo={grupoParcelaId} (GET, JSON)
     * Usado pelo modal "excluir parcelas": devolve todas as parcelas da
     * mesma compra (mesmo grupo_parcela_id), mesmo as que estao em outro
     * mes e por isso nao aparecem no extrato filtrado agora.
     *
     * @return void
     */
    public function grupoParcela()
    {
        $contaId = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        if (!$this->podeVisualizarConta($contaId, (int) $usuario['id'])) {
            http_response_code(403);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['erro' => 'Sem acesso a esta conta.']);
            exit;
        }

        $grupoParcelaId = (string) $this->request->getQuery('grupo');

        if ($grupoParcelaId === '') {
            http_response_code(400);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['erro' => 'Grupo de parcela nao informado.']);
            exit;
        }

        $parcelas = $this->model->listarGrupoParcela($grupoParcelaId, $contaId);

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['parcelas' => $parcelas]);
        exit;
    }

    /**
     * excluirEmMassa
     * URL: /Transacao/excluirEmMassa/{contaId} (POST)
     * Recebe uma lista de ids marcados (ids[]) e exclui exatamente essas
     * (soft-delete, mesma lixeira/prazo de 1 dia das transacoes normais).
     * Usado tanto pelo "Selecionar todos" + "Excluir selecionadas" geral do
     * topo da lista, quanto pelo modal "excluir parcelas de uma compra" --
     * nos dois casos e a mesma operacao: dado um conjunto de ids, excluir
     * so os marcados (RN pedida: apagar a compra inteira em grupo, ou
     * escolher so algumas parcelas -- a escolha acontece na tela, aqui so
     * executa o que foi marcado).
     *
     * @return void
     */
    public function excluirEmMassa()
    {
        $contaId = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        if (!$this->podeGerenciarConta($contaId, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $post = $this->request->getPost();
        $ids  = $post['ids'] ?? [];

        if (!is_array($ids) || empty($ids)) {
            Session::set('msgError', 'Selecione ao menos uma parcela para excluir.');
            return header("Location: /Transacao/extrato/{$contaId}");
        }

        $total = $this->model->excluirEmMassa($ids, $contaId);

        if ($total > 0) {
            Session::set('msgSucesso', $total . ' parcela(s) movida(s) para a lixeira. Você pode restaurar em até 1 dia.');
        } else {
            Session::set('msgError', 'Nenhuma parcela pode ser excluída.');
        }

        return header("Location: /Transacao/extrato/{$contaId}");
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
