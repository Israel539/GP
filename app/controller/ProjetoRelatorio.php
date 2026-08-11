<?php

namespace App\Controller;

use App\Library\Session;
use App\Model\MensagemProjetoModel;
use App\Model\ProjetoAtividadeModel;
use App\Model\ProjetoModel;
use App\Model\ProjetoRelatorioModel;

class ProjetoRelatorio extends BaseController
{
    // Limite de eventos detalhados na exportacao (timeline + chat juntos).
    // Acima disso, so os N mais recentes aparecem detalhados -- o resumo por
    // tipo (contarPorTipo()) sempre reflete o TOTAL real, mesmo truncado, pra
    // ninguem ler um numero errado no relatorio.
    const MAX_EVENTOS_DETALHE = 200;

    protected ProjetoRelatorioModel $model;
    protected ProjetoModel $projetoModel;
    protected ProjetoAtividadeModel $atividadeModel;
    protected MensagemProjetoModel $mensagemModel;

    public function __construct()
    {
        parent::__construct();
        $this->model          = $this->model('ProjetoRelatorio');
        $this->projetoModel   = $this->model('Projeto');
        $this->atividadeModel = $this->model('ProjetoAtividade');
        $this->mensagemModel  = $this->model('MensagemProjeto');
        $this->helper("crud");
    }

    /**
     * form
     * URL: /ProjetoRelatorio/form/{projetoId}
     * Formulario para escrever/editar o relatorio do projeto (em secoes
     * guiadas). So o dono pode acessar (RN do relatorio) -- mostra tambem
     * uma previa do historico (timeline + chat, ja resumido/agrupado), pra
     * servir de referencia enquanto escreve. O que entra no PDF/DOCX e
     * sempre puxado direto do banco na hora da exportacao, nunca do que foi
     * digitado aqui.
     *
     * @return void
     */
    public function form()
    {
        $projetoId = (int) $this->request->getAction();
        $usuario   = $this->usuarioLogado();

        if (!$this->autorizado($projetoId, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $projeto   = $this->projetoModel->buscarPorId($projetoId);
        $relatorio = $this->model->buscarPorProjeto($projetoId);
        $historico = $this->montarHistorico($projetoId);

        return $this->view("projetoRelatorioForm", [
            'projeto'   => $projeto,
            'relatorio' => $relatorio,
            'historico' => $historico,
        ]);
    }

    /**
     * salvar
     * URL: /ProjetoRelatorio/salvar/{projetoId} (POST)
     * Upsert do relatorio (RN: um unico relatorio por projeto, editavel --
     * ver ProjetoRelatorioModel::salvar()). So 'o_que_foi_feito' e
     * obrigatorio -- as outras secoes (contexto/decisoes/proximos_passos)
     * sao opcionais.
     *
     * @return void
     */
    public function salvar()
    {
        $projetoId = (int) $this->request->getAction();
        $usuario   = $this->usuarioLogado();

        if (!$this->autorizado($projetoId, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $post = $this->request->getPost();

        if (!$this->model->validate($post)) {
            Session::set('msgError', 'Preencha ao menos "O que foi feito" (minimo 10 caracteres) antes de salvar.');
            return header("Location: /ProjetoRelatorio/form/{$projetoId}");
        }

        $this->model->salvar($projetoId, (int) $usuario['id'], [
            'contexto'        => $post['contexto'] ?? '',
            'o_que_foi_feito' => $post['o_que_foi_feito'],
            'decisoes'        => $post['decisoes'] ?? '',
            'proximos_passos' => $post['proximos_passos'] ?? '',
        ]);

        Session::set('msgSucesso', 'Relatorio salvo. Agora voce ja pode exportar em PDF ou Word.');
        return header("Location: /ProjetoRelatorio/form/{$projetoId}");
    }

    /**
     * exportarPdf
     * URL: /ProjetoRelatorio/exportarPdf/{projetoId}
     *
     * @return void
     */
    public function exportarPdf()
    {
        [$projeto, $relatorio, $colaboradores, $historico, $usuario] = $this->dadosParaExportacao();

        if ($relatorio === null) {
            return; // dadosParaExportacao ja redirecionou
        }

        ob_start();
        require __DIR__ . '/../view/projetoRelatorioPdf.php';
        $html = ob_get_clean();

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans'); // fonte com suporte a acentuacao

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $nomeArquivo = $this->nomeArquivoExportacao($projeto['nome'], 'pdf');

        $dompdf->stream($nomeArquivo, ['Attachment' => true]);
        exit;
    }

    /**
     * exportarDocx
     * URL: /ProjetoRelatorio/exportarDocx/{projetoId}
     * Usa PHPWord (composer require phpoffice/phpword).
     *
     * @return void
     */
    public function exportarDocx()
    {
        [$projeto, $relatorio, $colaboradores, $historico, $usuario] = $this->dadosParaExportacao();

        if ($relatorio === null) {
            return; // dadosParaExportacao ja redirecionou
        }

        if (!class_exists(\PhpOffice\PhpWord\PhpWord::class)) {
            Session::set('msgError', 'Exportacao em Word indisponivel: biblioteca phpoffice/phpword nao instalada. Rode "composer require phpoffice/phpword" no projeto.');
            return header("Location: /ProjetoRelatorio/form/{$projeto['id']}");
        }

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language(\PhpOffice\PhpWord\Style\Language::PT_BR));

        $corLaranja = 'FD7E14';

        $estiloTituloProjeto = ['bold' => true, 'size' => 20, 'color' => $corLaranja];
        $estiloSubtitulo     = ['size' => 10, 'color' => '6C757D'];
        $estiloH2            = ['bold' => true, 'size' => 13, 'color' => '212529', 'spaceBefore' => 300, 'spaceAfter' => 120];
        $estiloH3            = ['bold' => true, 'size' => 11, 'color' => '495057', 'spaceBefore' => 200, 'spaceAfter' => 80];
        $estiloTexto         = ['size' => 11];
        $estiloMeta          = ['size' => 9, 'color' => '6C757D', 'italic' => true];

        $section = $phpWord->addSection(['marginLeft' => 900, 'marginRight' => 900]);

        $section->addText('GP — Relatório de Projeto', $estiloTituloProjeto);
        $section->addText(htmlspecialchars_decode($projeto['nome']), ['bold' => true, 'size' => 14]);
        $section->addText(
            'Status: ' . ucfirst(str_replace('_', ' ', $projeto['status']))
            . ' · Gerado em ' . date('d/m/Y H:i') . ' por ' . $usuario['nome'],
            $estiloSubtitulo
        );

        $section->addTextBreak(1);

        // Secoes guiadas do relatorio escrito pelo dono. Preserva quebras de
        // linha (uma linha em branco separa paragrafos), sem interpretar
        // nada como HTML/markup -- e texto puro que a pessoa digitou.
        $secoesRelatorio = [
            'Contexto'          => $relatorio['contexto'],
            'O que foi feito'   => $relatorio['o_que_foi_feito'],
            'Decisões tomadas'  => $relatorio['decisoes'],
            'Próximos passos'   => $relatorio['proximos_passos'],
        ];

        foreach ($secoesRelatorio as $rotulo => $texto) {
            if (trim((string) $texto) === '') {
                continue; // secao opcional nao preenchida -- nao aparece
            }

            $section->addText($rotulo, $estiloH3);
            $paragrafos = preg_split('/\n{2,}/', trim($texto));
            foreach ($paragrafos as $paragrafo) {
                $linhas = explode("\n", $paragrafo);
                $textrun = $section->addTextRun(['spaceAfter' => 160]);
                foreach ($linhas as $i => $linha) {
                    if ($i > 0) {
                        $textrun->addTextBreak();
                    }
                    $textrun->addText($linha, $estiloTexto);
                }
            }
        }

        // Participantes
        $section->addText('Participantes', $estiloH2);
        $tabelaColab = $section->addTable(['borderSize' => 4, 'borderColor' => 'DEE2E6', 'cellMargin' => 80]);
        $tabelaColab->addRow();
        $tabelaColab->addCell(4000)->addText('Nome', ['bold' => true, 'size' => 10]);
        $tabelaColab->addCell(2500)->addText('Papel', ['bold' => true, 'size' => 10]);
        $tabelaColab->addCell(2500)->addText('Entrou em', ['bold' => true, 'size' => 10]);
        foreach ($colaboradores as $c) {
            $tabelaColab->addRow();
            $tabelaColab->addCell(4000)->addText($c['nome'], $estiloTexto);
            $tabelaColab->addCell(2500)->addText(ucfirst($c['papel']), $estiloTexto);
            $tabelaColab->addCell(2500)->addText(date('d/m/Y', strtotime($c['entrou_em'])), $estiloTexto);
        }

        // Historico / caminho percorrido -- resumo por tipo primeiro (numero
        // sempre exato, mesmo se a lista detalhada abaixo for truncada),
        // depois a lista agrupada por dia.
        $section->addText('Histórico do projeto', $estiloH2);

        if (empty($historico['resumo'])) {
            $section->addText('Nenhum evento registrado na timeline deste projeto ainda.', $estiloMeta);
        } else {
            $tabelaResumo = $section->addTable(['borderSize' => 4, 'borderColor' => 'DEE2E6', 'cellMargin' => 80]);
            foreach ($historico['resumo'] as $item) {
                $tabelaResumo->addRow();
                $tabelaResumo->addCell(6000)->addText($item['rotulo'], $estiloTexto);
                $tabelaResumo->addCell(1500)->addText((string) $item['quantidade'], array_merge($estiloTexto, ['bold' => true]));
            }

            $section->addTextBreak(1);

            if ($historico['truncado']) {
                $section->addText(
                    'Exibindo os ' . self::MAX_EVENTOS_DETALHE . ' eventos mais recentes de ' . $historico['total_eventos'] . ' no total. Os números acima, porém, contam TODOS os eventos.',
                    $estiloMeta
                );
                $section->addTextBreak(1);
            }

            foreach ($historico['dias'] as $dia) {
                $section->addText($dia['data_label'], $estiloH3);
                foreach ($dia['itens'] as $evento) {
                    $textrun = $section->addTextRun(['spaceAfter' => 100]);
                    $textrun->addText(date('H:i', strtotime($evento['data'])) . '  ', $estiloMeta);
                    $textrun->addText($evento['texto'], $estiloTexto);
                }
            }
        }

        $section->addTextBreak(2);
        $section->addText(
            'Relatório gerado automaticamente pelo GP a partir dos dados do projeto.',
            $estiloMeta
        );

        $nomeArquivo = $this->nomeArquivoExportacao($projeto['nome'], 'docx');

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save('php://output');
        exit;
    }

    /**
     * dadosParaExportacao
     * Busca (e autoriza) tudo que os dois formatos de exportacao precisam,
     * evitando duplicar a mesma logica em exportarPdf() e exportarDocx().
     * Se algo bloquear (sem acesso, sem relatorio salvo ainda), ja redireciona
     * e devolve $relatorio = null -- o Controller que chamou so precisa
     * checar isso e dar return.
     *
     * @return array [$projeto, $relatorio|null, $colaboradores, $historico, $usuario]
     */
    protected function dadosParaExportacao(): array
    {
        $projetoId = (int) $this->request->getAction();
        $usuario   = $this->usuarioLogado();

        if (!$this->autorizado($projetoId, (int) $usuario['id'])) {
            $this->negarAcesso();
            return [[], null, [], [], $usuario];
        }

        $projeto   = $this->projetoModel->buscarPorId($projetoId);
        $relatorio = $this->model->buscarPorProjeto($projetoId);

        if (count($relatorio) === 0) {
            Session::set('msgError', 'Escreva e salve o relatorio antes de exportar.');
            header("Location: /ProjetoRelatorio/form/{$projetoId}");
            return [$projeto, null, [], [], $usuario];
        }

        $colaboradores = $this->projetoModel->listarColaboradores($projetoId);
        $historico     = $this->montarHistorico($projetoId);

        return [$projeto, $relatorio, $colaboradores, $historico, $usuario];
    }

    /**
     * montarHistorico
     * Junta a timeline de atividades (ProjetoAtividadeModel) com o chat do
     * projeto (MensagemProjetoModel) numa unica linha do tempo cronologica
     * -- e assim que o "caminho percorrido" fica completo (nao so
     * tarefas/colaboradores, mas tambem o que foi discutido).
     *
     * Devolve:
     *  - 'resumo': contagem por tipo de evento (sempre do TOTAL real)
     *  - 'dias': eventos agrupados por dia, ja no limite de detalhe
     *  - 'total_eventos', 'truncado'
     *
     * O agrupamento por dia + resumo por tipo existe pra resolver o mesmo
     * problema que uma lista crua de eventos tem num projeto de meses
     * ativo: vira repetitivo e dificil de ler. Agrupado, quem le entende o
     * ritmo do projeto ("dia tal teve bastante coisa") sem rolar uma lista
     * gigante de linhas identicas.
     *
     * @param int $projetoId
     * @return array
     */
    protected function montarHistorico(int $projetoId): array
    {
        $atividades = $this->atividadeModel->listarPorProjeto($projetoId);
        $mensagens  = $this->mensagemModel->listarPorProjeto($projetoId);

        $eventos = [];

        foreach ($atividades as $a) {
            $eventos[] = [
                'data'  => $a['criado_em'],
                'tipo'  => $a['tipo'],
                'texto' => $a['descricao'],
            ];
        }

        foreach ($mensagens as $m) {
            $eventos[] = [
                'data'  => $m['enviado_em'],
                'tipo'  => 'mensagem',
                'texto' => $m['autor_nome'] . ' comentou no chat: “' . $m['mensagem'] . '”',
            ];
        }

        usort($eventos, fn($a, $b) => strtotime($a['data']) <=> strtotime($b['data']));

        $totalEventos = count($eventos);
        $resumo       = $this->resumoPorTipo($eventos);
        $truncado     = $totalEventos > self::MAX_EVENTOS_DETALHE;

        if ($truncado) {
            $eventos = array_slice($eventos, -self::MAX_EVENTOS_DETALHE);
        }

        $dias = [];
        foreach ($eventos as $evento) {
            $chaveDia = date('Y-m-d', strtotime($evento['data']));

            if (!isset($dias[$chaveDia])) {
                $dias[$chaveDia] = [
                    'data_label' => date('d/m/Y', strtotime($evento['data'])),
                    'itens'      => [],
                ];
            }

            $dias[$chaveDia]['itens'][] = $evento;
        }

        return [
            'resumo'        => $resumo,
            'dias'          => array_values($dias),
            'total_eventos' => $totalEventos,
            'truncado'      => $truncado,
        ];
    }

    /**
     * resumoPorTipo
     * Contagem de eventos por tipo, com rotulo pronto pra exibir. Calculado
     * ANTES de qualquer corte por MAX_EVENTOS_DETALHE, entao os numeros aqui
     * sempre batem com a realidade do projeto inteiro.
     *
     * @param array $eventos
     * @return array
     */
    protected function resumoPorTipo(array $eventos): array
    {
        $rotulos = [
            ProjetoAtividadeModel::TIPO_PROJETO_CRIADO       => 'Projeto criado',
            ProjetoAtividadeModel::TIPO_PROJETO_CONCLUIDO    => 'Projeto concluído',
            ProjetoAtividadeModel::TIPO_COLABORADOR_ENTROU   => 'Entradas de colaborador',
            ProjetoAtividadeModel::TIPO_COLABORADOR_SAIU     => 'Saídas de colaborador',
            ProjetoAtividadeModel::TIPO_COLABORADOR_REMOVIDO => 'Remoções de colaborador',
            ProjetoAtividadeModel::TIPO_TAREFA_CRIADA        => 'Tarefas criadas',
            ProjetoAtividadeModel::TIPO_TAREFA_MOVIDA        => 'Movimentações de tarefas',
            ProjetoAtividadeModel::TIPO_TAREFA_EXCLUIDA      => 'Tarefas excluídas',
            'mensagem'                                       => 'Mensagens no chat',
        ];

        $contagem = [];
        foreach ($eventos as $evento) {
            $contagem[$evento['tipo']] = ($contagem[$evento['tipo']] ?? 0) + 1;
        }

        $resumo = [];
        foreach ($contagem as $tipo => $quantidade) {
            $resumo[] = [
                'rotulo'     => $rotulos[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo)),
                'quantidade' => $quantidade,
            ];
        }

        return $resumo;
    }

    /**
     * nomeArquivoExportacao
     * Mesmo padrao usado em Transacao::nomeArquivoExportacao().
     *
     * @param string $nomeProjeto
     * @param string $extensao
     * @return string
     */
    protected function nomeArquivoExportacao(string $nomeProjeto, string $extensao): string
    {
        $semAcento = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nomeProjeto) ?: $nomeProjeto;
        $slug      = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $semAcento), '-'));

        return "relatorio_{$slug}_" . date('Ymd_Hi') . ".{$extensao}";
    }

    /**
     * autorizado
     * RN do relatorio: somente o dono do projeto pode escrever, editar e
     * exportar o relatorio. Diferente de Projeto::podeGerenciar() (que vale
     * pra qualquer colaborador) -- aqui e mais restrito de proposito.
     *
     * @param int $projetoId
     * @param int $usuarioId
     * @return bool
     */
    protected function autorizado(int $projetoId, int $usuarioId): bool
    {
        return $this->projetoModel->usuarioEhDono($projetoId, $usuarioId);
    }

    /**
     * negarAcesso
     *
     * @return void
     */
    protected function negarAcesso()
    {
        http_response_code(403);
        Session::set('msgError', 'Somente o dono do projeto pode acessar o relatorio.');
        return header("Location: /Projeto");
    }
}
