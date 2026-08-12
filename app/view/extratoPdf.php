<?php

/**
 * View exclusiva pra geracao de PDF via Dompdf -- por isso NAO inclui
 * comuns/header.php nem comuns/footer.php (aquele layout depende de
 * Bootstrap/flexbox/CDN, que o Dompdf nao renderiza direito). Aqui e HTML +
 * CSS bem simples, so tabela e blocos, pensado pra imprimir.
 *
 * @var array $conta
 * @var array $transacoes
 * @var array $filtros
 * @var array $totais ['receitas' => float, 'despesas' => float]
 * @var float $saldoAtual Saldo real da conta agora (RN08) -- independente do periodo exportado
 * @var string|null $categoriaFiltroNome
 * @var array $usuario
 */
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
            font-size: 13px;
            color: #495057;
            margin-top: 2px;
        }

        .info-periodo {
            font-size: 10px;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .saldo-conta {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px 10px;
            margin-bottom: 14px;
        }

        .saldo-conta .rotulo {
            font-size: 9.5px;
            color: #6c757d;
            text-transform: uppercase;
        }

        .saldo-conta .valor {
            font-size: 16px;
            font-weight: bold;
        }

        .saldo-positivo {
            color: #198754;
        }

        .saldo-negativo {
            color: #dc3545;
        }

        table.transacoes {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        table.transacoes th {
            background: #f1f3f5;
            text-align: left;
            padding: 5px 6px;
            border-bottom: 1px solid #dee2e6;
            font-size: 10px;
            text-transform: uppercase;
        }

        table.transacoes td {
            padding: 5px 6px;
            border-bottom: 1px solid #eee;
            font-size: 10.5px;
        }

        .valor-receita {
            color: #198754;
        }

        .valor-despesa {
            color: #dc3545;
        }

        .text-right {
            text-align: right;
        }

        .badge-origem {
            font-size: 8.5px;
            color: #6c757d;
        }

        table.resumo {
            width: 260px;
            margin-left: auto;
            border-collapse: collapse;
        }

        table.resumo td {
            padding: 4px 6px;
            font-size: 11px;
        }

        table.resumo .linha-total td {
            border-top: 1px solid #495057;
            font-weight: bold;
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
        <div class="subtitulo">Extrato de conta &mdash; <?= htmlspecialchars($conta['nome']) ?> (<?= htmlspecialchars(ucfirst($conta['tipo'])) ?>)</div>
    </div>

    <div class="info-periodo">
        Periodo:
        <?= !empty($filtros['data_inicio']) ? htmlspecialchars(date('d/m/Y', strtotime($filtros['data_inicio']))) : 'inicio' ?>
        ate
        <?= !empty($filtros['data_fim']) ? htmlspecialchars(date('d/m/Y', strtotime($filtros['data_fim']))) : 'hoje' ?>
        <?php if (!empty($categoriaFiltroNome)): ?>
            &nbsp;&middot;&nbsp; Categoria: <?= htmlspecialchars($categoriaFiltroNome) ?>
        <?php endif; ?>
        <br>
        Gerado em <?= date('d/m/Y H:i') ?> por <?= htmlspecialchars($usuario['nome'] ?? '') ?>
    </div>

    <div class="saldo-conta">
        <div class="rotulo">Saldo atual em conta (agora, RN08)</div>
        <div class="valor <?= $saldoAtual < 0 ? 'saldo-negativo' : 'saldo-positivo' ?>">
            R$ <?= number_format($saldoAtual, 2, ',', '.') ?>
        </div>
    </div>

    <?php if (empty($transacoes)): ?>
        <p>Nenhuma transacao encontrada para o periodo/filtro selecionado.</p>
    <?php else: ?>
        <table class="transacoes">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descricao</th>
                    <th>Categoria</th>
                    <th>Modalidade</th>
                    <th class="text-right">Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transacoes as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($t['data_fato_gerador']))) ?></td>
                        <td>
                            <?= htmlspecialchars($t['descricao']) ?>
                            <?php if ($t['origem'] === 'api_openfinance'): ?>
                                <span class="badge-origem">(Open Finance)</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($t['categoria_nome'] ?? '-') ?></td>
                        <td><?= htmlspecialchars(ucfirst($t['modalidade'])) ?></td>
                        <td class="text-right <?= $t['tipo'] === 'despesa' ? 'valor-despesa' : 'valor-receita' ?>">
                            <?= $t['tipo'] === 'despesa' ? '-' : '+' ?> R$ <?= number_format((float) $t['valor'], 2, ',', '.') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <table class="resumo">
        <tr>
            <td>Total de receitas</td>
            <td class="text-right valor-receita">R$ <?= number_format($totais['receitas'], 2, ',', '.') ?></td>
        </tr>
        <tr>
            <td>Total de despesas</td>
            <td class="text-right valor-despesa">R$ <?= number_format($totais['despesas'], 2, ',', '.') ?></td>
        </tr>
        <tr class="linha-total">
            <td>Saldo do período filtrado (receitas − despesas)</td>
            <td class="text-right">R$ <?= number_format($totais['receitas'] - $totais['despesas'], 2, ',', '.') ?></td>
        </tr>
    </table>

    <div class="rodape">
        Relatorio gerado automaticamente pelo GP. Este documento nao substitui o extrato oficial da instituicao financeira.
    </div>

</body>

</html>