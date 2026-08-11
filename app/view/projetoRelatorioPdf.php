<?php

/**
 * View exclusiva pra geracao de PDF via Dompdf -- por isso NAO inclui
 * comuns/header.php nem comuns/footer.php (mesmo motivo do extratoPdf.php:
 * aquele layout depende de Bootstrap/flexbox/CDN, que o Dompdf nao
 * renderiza direito).
 *
 * @var array $projeto
 * @var array $relatorio ['contexto','o_que_foi_feito','decisoes','proximos_passos','atualizado_em', ...]
 * @var array $colaboradores
 * @var array $historico ['resumo' => [...], 'dias' => [...], 'total_eventos' => int, 'truncado' => bool]
 * @var array $usuario
 */

$secoesRelatorio = [
    'Contexto'         => $relatorio['contexto'],
    'O que foi feito'  => $relatorio['o_que_foi_feito'],
    'Decisões tomadas' => $relatorio['decisoes'],
    'Próximos passos'  => $relatorio['proximos_passos'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #212529;
        }

        .cabecalho {
            border-bottom: 2px solid #fd7e14;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .cabecalho .logo {
            font-size: 18px;
            font-weight: bold;
            color: #fd7e14;
        }

        .cabecalho .subtitulo {
            font-size: 15px;
            font-weight: bold;
            color: #212529;
            margin-top: 2px;
        }

        .info-topo {
            font-size: 10px;
            color: #6c757d;
            margin-bottom: 16px;
        }

        h2.secao {
            font-size: 13px;
            color: #212529;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 4px;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        h3.subsecao {
            font-size: 11.5px;
            color: #495057;
            margin-top: 14px;
            margin-bottom: 6px;
        }

        .conteudo-relatorio {
            font-size: 11px;
            line-height: 1.5;
            text-align: justify;
            white-space: pre-line;
        }

        table.colaboradores,
        table.resumo,
        table.timeline {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table.colaboradores th,
        table.resumo th {
            background: #f1f3f5;
            text-align: left;
            padding: 5px 6px;
            border-bottom: 1px solid #dee2e6;
            font-size: 9.5px;
            text-transform: uppercase;
        }

        table.colaboradores td,
        table.resumo td {
            padding: 5px 6px;
            border-bottom: 1px solid #eee;
            font-size: 10.5px;
            vertical-align: top;
        }

        table.resumo td.qtd {
            text-align: right;
            font-weight: bold;
            width: 50px;
        }

        .aviso-truncado {
            font-size: 9.5px;
            color: #6c757d;
            font-style: italic;
            margin-bottom: 10px;
        }

        .dia-grupo {
            margin-bottom: 10px;
        }

        table.timeline td.data {
            white-space: nowrap;
            color: #6c757d;
            font-size: 9.5px;
            width: 55px;
        }

        table.timeline td {
            padding: 3px 6px;
            font-size: 10.5px;
            vertical-align: top;
            border-bottom: none;
        }

        .rodape {
            margin-top: 20px;
            font-size: 9px;
            color: #adb5bd;
            border-top: 1px solid #eee;
            padding-top: 6px;
        }
    </style>
</head>

<body>

    <div class="cabecalho">
        <div class="logo">GP</div>
        <div class="subtitulo">Relatório de projeto &mdash; <?= htmlspecialchars($projeto['nome']) ?></div>
    </div>

    <div class="info-topo">
        Status: <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $projeto['status']))) ?>
        &nbsp;&middot;&nbsp;
        Última atualização do relatório: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($relatorio['atualizado_em']))) ?>
        <br>
        Gerado em <?= date('d/m/Y H:i') ?> por <?= htmlspecialchars($usuario['nome'] ?? '') ?>
    </div>

    <?php foreach ($secoesRelatorio as $rotulo => $texto): ?>
        <?php if (trim((string) $texto) === '') continue; // secao opcional nao preenchida -- nao aparece ?>
        <h3 class="subsecao"><?= htmlspecialchars($rotulo) ?></h3>
        <div class="conteudo-relatorio"><?= htmlspecialchars($texto) ?></div>
    <?php endforeach; ?>

    <h2 class="secao">Participantes</h2>
    <?php if (empty($colaboradores)): ?>
        <p>Nenhum participante registrado.</p>
    <?php else: ?>
        <table class="colaboradores">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Papel</th>
                    <th>Entrou em</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($colaboradores as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['nome']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($c['papel'])) ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($c['entrou_em']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2 class="secao">Histórico do projeto</h2>

    <?php if (empty($historico['resumo'])): ?>
        <p>Nenhum evento registrado na timeline deste projeto ainda.</p>
    <?php else: ?>
        <table class="resumo">
            <thead>
                <tr>
                    <th>Resumo</th>
                    <th style="text-align:right;">Qtd.</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historico['resumo'] as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['rotulo']) ?></td>
                        <td class="qtd"><?= (int) $item['quantidade'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($historico['truncado']): ?>
            <p class="aviso-truncado">
                Exibindo os eventos mais recentes de <?= (int) $historico['total_eventos'] ?> no total.
                Os números da tabela acima, porém, contam <strong>todos</strong> os eventos do projeto.
            </p>
        <?php endif; ?>

        <?php foreach ($historico['dias'] as $dia): ?>
            <div class="dia-grupo">
                <h3 class="subsecao"><?= htmlspecialchars($dia['data_label']) ?></h3>
                <table class="timeline">
                    <tbody>
                        <?php foreach ($dia['itens'] as $evento): ?>
                            <tr>
                                <td class="data"><?= htmlspecialchars(date('H:i', strtotime($evento['data']))) ?></td>
                                <td><?= htmlspecialchars($evento['texto']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="rodape">
        Relatório gerado automaticamente pelo GP a partir dos dados do projeto. O histórico acima reflete apenas os eventos registrados a partir da implantação desta funcionalidade.
    </div>

</body>

</html>
