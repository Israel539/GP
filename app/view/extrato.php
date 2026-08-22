<?php
/**
 * @var array $conta
 * @var array $transacoes
 * @var float $saldoAtual
 * @var array $categorias
 * @var array $cartoes
 * @var array $tags
 * @var array $filtros
 * @var array $periodo
 * @var array $totaisFiltro
 * @var array $lixeira
 * @var array $usuario
 */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container-fluid py-4">

    <div class="row mb-2">
        <div class="col-12">
            <a href="/Conta" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Trocar de conta
            </a>
        </div>
    </div>

    <div class="row mb-3 align-items-center">
        <div class="col-md-6">
            <h2><?= htmlspecialchars($conta['nome']) ?></h2>
            <span class="text-muted"><?= htmlspecialchars(ucfirst($conta['tipo'])) ?></span>
        </div>
        <div class="col-md-6 text-end">
            <?php $saldoTotal = $saldoAtual + (float) ($usuario['saldo_dinheiro'] ?? 0); ?>
            <div class="fs-3 <?= $saldoTotal < 0 ? 'text-danger' : 'text-success' ?>">
                R$ <?= number_format($saldoTotal, 2, ',', '.') ?>
            </div>
            <span class="small text-muted">Saldo total (conta + dinheiro físico)</span>
        </div>
    </div>

    <!-- Widget opcional: dinheiro fisico + saldo desta conta (RN08) + total.
         Desligado por padrao -- so aparece se o usuario ativar a checkbox
         (preferencia salva por usuario, vale pra todas as contas). -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-center <?= !empty($usuario['exibir_saldo_dinheiro']) ? 'mb-3' : '' ?>">
                <strong class="small">Resumo financeiro</strong>
                <form action="/Conta/alternarExibirSaldoDinheiro" method="POST" class="d-flex align-items-center gap-2 mb-0">
                    <?= \App\Library\Csrf::getHiddenField() ?>
                    <input type="hidden" name="voltar_para" value="/Transacao/extrato/<?= (int) $conta['id'] ?>">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="chkExibirDinheiro"
                            name="exibir" value="1" onchange="this.form.submit()"
                            <?= !empty($usuario['exibir_saldo_dinheiro']) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="chkExibirDinheiro">Mostrar dinheiro físico</label>
                    </div>
                </form>
            </div>

            <?php if (!empty($usuario['exibir_saldo_dinheiro'])): ?>
                <div class="row text-center g-2">
                    <div class="col-md-4 border-end">
                        <div class="small text-muted mb-1">Dinheiro físico</div>
                        <form action="/Conta/atualizarSaldoDinheiro" method="POST"
                            class="d-flex justify-content-center align-items-center gap-1">
                            <?= \App\Library\Csrf::getHiddenField() ?>
                            <input type="hidden" name="voltar_para" value="/Transacao/extrato/<?= (int) $conta['id'] ?>">
                            <span class="small">R$</span>
                            <input type="text" name="saldo_dinheiro" class="form-control form-control-sm text-center"
                                style="max-width: 110px;"
                                value="<?= number_format((float) $usuario['saldo_dinheiro'], 2, ',', '') ?>">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Salvar</button>
                        </form>
                    </div>
                    <div class="col-md-4 border-end">
                        <div class="small text-muted mb-1">Saldo desta conta</div>
                        <form action="/Conta/atualizarSaldoConta" method="POST"
                            class="d-flex justify-content-center align-items-center gap-1">
                            <?= \App\Library\Csrf::getHiddenField() ?>
                            <input type="hidden" name="conta_id" value="<?= (int) $conta['id'] ?>">
                            <input type="hidden" name="voltar_para" value="/Transacao/extrato/<?= (int) $conta['id'] ?>">
                            <span class="small">R$</span>
                            <input type="text" name="saldo_conta" class="form-control form-control-sm text-center"
                                style="max-width: 110px;"
                                value="<?= number_format($saldoAtual, 2, ',', '') ?>">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Salvar</button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted mb-1">Total (dinheiro + esta conta)</div>
                        <?php $total = $saldoAtual + (float) $usuario['saldo_dinheiro']; ?>
                        <div class="fs-6 fw-bold <?= $total < 0 ? 'text-danger' : 'text-success' ?>">
                            R$ <?= number_format($total, 2, ',', '.') ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?= mensagens() ?>

    <div class="row">
        <!-- Novo lancamento -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">Novo lancamento</div>
                <div class="card-body">
                    <form action="/Transacao/lancar" method="POST" id="formLancamento">
                        <?= \App\Library\Csrf::getHiddenField() ?>
                        <input type="hidden" name="conta_id" value="<?= (int) $conta['id'] ?>">

                        <div class="mb-2">
                            <label class="form-label small">Descricao</label>
                            <input type="text" name="descricao" class="form-control form-control-sm" required>
                            <?= campoErro('descricao') ?>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small">Valor</label>
                                <input type="text" name="valor" class="form-control form-control-sm" placeholder="0,00" required>
                                <?= campoErro('valor') ?>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Tipo</label>
                                <select name="tipo" class="form-select form-select-sm">
                                    <option value="despesa">Despesa</option>
                                    <option value="receita">Receita</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small">Modalidade</label>
                            <select name="modalidade" id="modalidade" class="form-select form-select-sm" onchange="alternarCampoCartao()">
                                <option value="pix">Pix</option>
                                <option value="boleto">Boleto</option>
                                <option value="debito">Debito</option>
                                <option value="credito">Credito</option>
                                <option value="dinheiro">Dinheiro</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>

                        <div class="mb-2" id="campoCartao" style="display:none;">
                            <label class="form-label small">Cartao (obrigatorio p/ credito)</label>
                            <select name="cartao_id" class="form-select form-select-sm">
                                <option value="">-- selecione --</option>
                                <?php foreach ($cartoes as $cartao): ?>
                                    <option value="<?= (int) $cartao['id'] ?>"><?= htmlspecialchars($cartao['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-2" id="campoParcelas" style="display:none;">
                            <label class="form-label small">Parcelas</label>
                            <select name="parcelas" class="form-select form-select-sm">
                                <?php for ($i = 1; $i <= 24; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i === 1 ? 'A vista (1x)' : "{$i}x" ?></option>
                                <?php endfor; ?>
                            </select>
                            <div class="form-text">O valor digitado acima e o TOTAL da compra -- cada parcela cai na fatura do seu proprio mes.</div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small">Data do fato</label>
                                <input type="date" name="data_fato_gerador" class="form-control form-control-sm"
                                    value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Competencia</label>
                                <input type="date" name="data_competencia" class="form-control form-control-sm"
                                    value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small">Categoria <a href="/Categoria" class="small">(gerenciar)</a></label>
                            <select name="categoria_id" class="form-select form-select-sm">
                                <option value="">-- sem categoria --</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= (int) $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?> (<?= $cat['tipo'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Tags (separadas por virgula)</label>
                            <input type="text" name="tags" class="form-control form-control-sm" placeholder="mercado, casa">
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100">Lancar</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Extrato -->
        <div class="col-md-8">

            <form action="/Transacao/extrato/<?= (int) $conta['id'] ?>" method="GET" class="card card-body mb-3">
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <?php
                    $queryCategoria = !empty($filtros['categoria_id']) ? '&categoria_id=' . (int) $filtros['categoria_id'] : '';
                    ?>
                    <a href="?mes=<?= htmlspecialchars($periodo['mes_anterior']) . $queryCategoria ?>"
                        class="btn btn-outline-secondary btn-sm" title="Mês anterior">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <strong class="mx-1"><?= htmlspecialchars($periodo['rotulo']) ?></strong>
                    <a href="?mes=<?= htmlspecialchars($periodo['mes_seguinte']) . $queryCategoria ?>"
                        class="btn btn-outline-secondary btn-sm" title="Próximo mês">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <?php if (!$periodo['eh_mes_atual'] || $periodo['usando_intervalo_customizado']): ?>
                        <a href="?mes=<?= htmlspecialchars($periodo['mes_hoje']) . $queryCategoria ?>" class="btn btn-link btn-sm">
                            Voltar para o mês atual
                        </a>
                    <?php endif; ?>
                </div>

                <input type="hidden" name="mes" value="<?= htmlspecialchars($periodo['mes']) ?>">

                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small">Categoria</label>
                        <select name="categoria_id" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= (int) $cat['id'] ?>"
                                    <?= (($filtros['categoria_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100">Filtrar</button>
                    </div>
                    <div class="col-md-5 text-end">
                        <a class="small" data-bs-toggle="collapse" href="#filtroPeriodoCustom" role="button">
                            Escolher um período específico
                        </a>
                    </div>
                </div>

                <div class="collapse <?= $periodo['usando_intervalo_customizado'] ? 'show' : '' ?> mt-2" id="filtroPeriodoCustom">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small">De</label>
                            <input type="date" name="data_inicio" class="form-control form-control-sm"
                                value="<?= htmlspecialchars($periodo['usando_intervalo_customizado'] ? ($filtros['data_inicio'] ?? '') : '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Até</label>
                            <input type="date" name="data_fim" class="form-control form-control-sm"
                                value="<?= htmlspecialchars($periodo['usando_intervalo_customizado'] ? ($filtros['data_fim'] ?? '') : '') ?>">
                        </div>
                        <div class="col-md-4">
                            <div class="form-text">Preenchendo aqui, o filtro por mês acima é ignorado.</div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($filtros['categoria_id'])): ?>
                    <?php
                    $categoriaSelecionada = 'Categoria selecionada';
                    foreach ($categorias as $categoria) {
                        if ((int) $categoria['id'] === (int) $filtros['categoria_id']) {
                            $categoriaSelecionada = $categoria['nome'];
                            break;
                        }
                    }
                    ?>
                    <div class="alert alert-light border mt-2 mb-0 py-2 small">
                        <strong><?= htmlspecialchars($categoriaSelecionada) ?></strong>:
                        <?= count($transacoes) ?> lançamento(<?= count($transacoes) === 1 ? '' : 's' ?>),
                        <span class="text-danger fw-semibold">
                            total gasto: R$ <?= number_format($totaisFiltro['despesas'], 2, ',', '.') ?>
                        </span>
                        <?php if ($totaisFiltro['receitas'] > 0): ?>
                            <span class="text-success ms-2">
                                receitas: R$ <?= number_format($totaisFiltro['receitas'], 2, ',', '.') ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Exportar reusa os mesmos campos de filtro acima (formaction
                     troca so o destino do submit, sem precisar de JS nem de
                     duplicar o formulario) -- entao exporta exatamente o
                     periodo/categoria que estiver selecionado no momento. -->
                <div class="mt-2 text-end">
                    <button type="submit" formaction="/Transacao/exportarCsv/<?= (int) $conta['id'] ?>"
                        class="btn btn-outline-success btn-sm">
                        <i class="bi bi-filetype-csv"></i> Exportar CSV
                    </button>
                    <button type="submit" formaction="/Transacao/exportarPdf/<?= (int) $conta['id'] ?>"
                        class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
                    </button>
                </div>
            </form>

            <?php if (empty($transacoes)): ?>
                <div class="alert alert-secondary">Nenhuma transacao encontrada.</div>
            <?php else: ?>
                <form action="/Transacao/excluirEmMassa/<?= (int) $conta['id'] ?>" method="POST" class="form-selecao-massa" data-lista="transacoes">
                    <?= \App\Library\Csrf::getHiddenField() ?>
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                            <div class="form-check mb-0">
                                <input class="form-check-input chk-selecionar-todos" type="checkbox"
                                    id="selTodosTransacoes" data-alvo="transacoes">
                                <label class="form-check-label small" for="selTodosTransacoes">Selecionar todos</label>
                            </div>
                            <button type="submit" class="btn btn-sm btn-outline-danger btn-excluir-selecionados" disabled
                                onclick="return confirm('Mover as transações selecionadas para a lixeira? Você poderá restaurar em até 1 dia.')">
                                <i class="bi bi-trash"></i> Excluir selecionadas
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div style="max-height: 480px; overflow-y: auto;" class="p-3">
                                <ul class="list-group list-group-flush mb-0">
                                    <?php foreach ($transacoes as $t): ?>
                                        <?php
                                        $imutavel  = $t['origem'] === 'api_openfinance';
                                    $ehParcela = !empty($t['parcela_total']) && (int) $t['parcela_total'] > 1;
                                    ?>
                                    <li class="list-group-item">
                                        <div class="d-flex flex-nowrap align-items-center" style="gap: 10px;">
                                            <div style="flex: 0 0 24px;">
                                                <?php if (!$imutavel): ?>
                                                    <input class="form-check-input chk-item chk-transacoes" type="checkbox" name="ids[]" value="<?= (int) $t['id'] ?>">
                                                <?php endif; ?>
                                            </div>
                                            <div style="flex: 1 1 200px; min-width: 140px;">
                                                <strong><?= htmlspecialchars($t['descricao']) ?></strong>
                                                <div class="small text-muted">
                                                    <?= htmlspecialchars($t['data_fato_gerador']) ?>
                                                    &middot; <?= htmlspecialchars($t['modalidade']) ?>
                                                    <?php if ($ehParcela): ?>
                                                        <span class="badge bg-warning text-dark" title="Parcela <?= (int) $t['parcela_atual'] ?> de <?= (int) $t['parcela_total'] ?>">
                                                            <?= (int) $t['parcela_atual'] ?>/<?= (int) $t['parcela_total'] ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($imutavel): ?>
                                                        <span class="badge bg-secondary" title="Importado via Open Finance -- RN07: so categoria/tags editaveis">🔒 API</span>
                                                    <?php elseif ($t['origem'] === 'recorrente'): ?>
                                                        <span class="badge bg-info text-dark" title="Lancada automaticamente por uma transacao recorrente">🔁 Recorrente</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div style="flex: 0 0 100px;">
                                                <span class="<?= $t['tipo'] === 'despesa' ? 'text-danger' : 'text-success' ?>">
                                                    <?= $t['tipo'] === 'despesa' ? '-' : '+' ?> R$ <?= number_format((float) $t['valor'], 2, ',', '.') ?>
                                                </span>
                                            </div>
                                            <form action="/Transacao/atualizarCategoria/<?= (int) $t['id'] ?>" method="POST"
                                                class="d-flex flex-nowrap align-items-center" style="flex: 0 0 auto; gap: 6px;">
                                                <?= \App\Library\Csrf::getHiddenField() ?>
                                                <select name="categoria_id" class="form-select form-select-sm" style="flex: 0 0 150px;">
                                                    <option value="">-- sem categoria --</option>
                                                    <?php foreach ($categorias as $cat): ?>
                                                        <option value="<?= (int) $cat['id'] ?>"
                                                            <?= ((int) ($t['categoria_id'] ?? 0) === (int) $cat['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($cat['nome']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="text" name="tags" class="form-control form-control-sm" style="flex: 0 0 100px;"
                                                    placeholder="tags..."
                                                    value="<?= htmlspecialchars(implode(', ', array_column($t['tags'], 'nome'))) ?>">
                                                <button type="submit" class="btn btn-outline-primary btn-sm text-nowrap" style="flex: 0 0 auto;">Salvar</button>
                                            </form>
                                            <div style="flex: 0 0 160px;" class="text-end">
                                                <?php if (!$imutavel): ?>
                                                    <a href="/Transacao/editar/<?= (int) $t['id'] ?>" class="btn btn-sm btn-outline-secondary mb-1">Editar</a>
                                                    <?php if ($ehParcela): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger mb-1 btn-abrir-modal-parcela"
                                                            data-grupo="<?= htmlspecialchars($t['grupo_parcela_id']) ?>"
                                                            data-descricao="<?= htmlspecialchars($t['descricao']) ?>">
                                                            Excluir
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" formaction="/Transacao/excluir/<?= (int) $t['id'] ?>"
                                                            class="btn btn-sm btn-outline-danger mb-1"
                                                            onclick="return confirm('Mover esta transacao para a lixeira? Voce podera restaurar em ate 1 dia.')">
                                                            Excluir
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>

            <!-- Modal compartilhado: excluir parcelas de uma compra (RN pedida:
                 apagar a compra inteira em grupo, ou escolher so algumas). O
                 conteudo e carregado via fetch quando abre, porque as parcelas
                 de uma mesma compra podem estar espalhadas por varios meses --
                 o extrato so mostra o mes filtrado agora, mas o grupo inteiro
                 precisa aparecer aqui independente disso. -->
            <div class="modal fade" id="modalExcluirParcelas" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="/Transacao/excluirEmMassa/<?= (int) $conta['id'] ?>" method="POST" id="formExcluirParcelas">
                            <?= \App\Library\Csrf::getHiddenField() ?>
                            <div class="modal-header">
                                <h5 class="modal-title">Excluir parcelas de "<span id="modalParcelaDescricao"></span>"</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            <div class="modal-body">
                                <p class="small text-muted">
                                    Marque quais parcelas dessa compra você quer excluir. Todas vêm marcadas por
                                    padrão — desmarque as que quer manter.
                                </p>
                                <div id="modalParcelaLista">
                                    <div class="text-center text-muted py-3">Carregando...</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-danger">Excluir selecionadas</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lixeira: transacoes excluidas nas ultimas 24h, ainda dentro
                 do prazo de restauracao. Passado esse prazo elas somem daqui
                 (e o cron ja as apaga de vez do banco). -->
            <?php if (!empty($lixeira)): ?>
                <div class="mt-4">
                    <a class="text-decoration-none" data-bs-toggle="collapse" href="#lixeiraTransacoes" role="button">
                        <i class="bi bi-trash3"></i> Lixeira (<?= count($lixeira) ?>)
                    </a>
                    <div class="collapse mt-2" id="lixeiraTransacoes">
                        <div class="alert alert-light border small mb-2">
                            Transações excluídas ficam aqui por até 1 dia, depois somem definitivamente.
                        </div>
                        <?php foreach ($lixeira as $t): ?>
                            <?php
                            $expiraEm      = strtotime($t['excluido_em']) + 86400;
                            $horasRestantes = max(0, (int) ceil(($expiraEm - time()) / 3600));
                            ?>
                            <div class="card mb-2 border-warning-subtle">
                                <div class="card-body py-2">
                                    <div class="row align-items-center">
                                        <div class="col-md-5">
                                            <span class="text-decoration-line-through text-muted"><?= htmlspecialchars($t['descricao']) ?></span>
                                            <div class="small text-muted">
                                                <?= htmlspecialchars($t['data_fato_gerador']) ?>
                                                &middot; excluída há <?= htmlspecialchars(date('d/m H:i', strtotime($t['excluido_em']))) ?>
                                                &middot; expira em <?= $horasRestantes ?>h
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <span class="text-muted">
                                                <?= $t['tipo'] === 'despesa' ? '-' : '+' ?> R$ <?= number_format((float) $t['valor'], 2, ',', '.') ?>
                                            </span>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <form action="/Transacao/restaurar/<?= (int) $t['id'] ?>" method="POST" class="d-inline">
                                                <?= \App\Library\Csrf::getHiddenField() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Restaurar
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
function alternarCampoCartao() {
    var modalidade = document.getElementById('modalidade').value;
    var ehCredito = (modalidade === 'credito');
    document.getElementById('campoCartao').style.display = ehCredito ? 'block' : 'none';
    document.getElementById('campoParcelas').style.display = ehCredito ? 'block' : 'none';

    // Fora do credito, parcelamento nao existe -- reseta pra "1x" (a vista)
    // pra nao mandar um "parcelas=3" escondido junto com uma modalidade que
    // nao suporta parcelamento.
    if (!ehCredito) {
        document.querySelector('select[name="parcelas"]').value = '1';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Selecionar todos / excluir selecionadas -- mesmo padrao reutilizavel
    // ja usado na Agenda (.form-selecao-massa / .chk-selecionar-todos /
    // .chk-item / .btn-excluir-selecionados), pra ficar identico em toda a
    // aplicacao.
    document.querySelectorAll('.form-selecao-massa').forEach(function (form) {
        var chkTodos = form.querySelector('.chk-selecionar-todos');
        var chkItens = form.querySelectorAll('.chk-item');
        var btnExcluir = form.querySelector('.btn-excluir-selecionados');

        function atualizarBotao() {
            var algumMarcado = Array.prototype.some.call(chkItens, function (c) { return c.checked; });
            btnExcluir.disabled = !algumMarcado;
        }

        if (chkTodos) {
            chkTodos.addEventListener('change', function () {
                chkItens.forEach(function (c) { c.checked = chkTodos.checked; });
                atualizarBotao();
            });
        }

        chkItens.forEach(function (c) {
            c.addEventListener('change', function () {
                if (!c.checked && chkTodos) {
                    chkTodos.checked = false;
                }
                atualizarBotao();
            });
        });

        atualizarBotao();
    });

    // Modal "excluir parcelas de uma compra" -- carrega via fetch pq as
    // parcelas de uma mesma compra podem estar em meses diferentes do que
    // esta sendo exibido agora no extrato.
    var modalEl = document.getElementById('modalExcluirParcelas');
    var modalParcelas = modalEl ? new bootstrap.Modal(modalEl) : null;
    var listaEl = document.getElementById('modalParcelaLista');
    var descEl = document.getElementById('modalParcelaDescricao');

    document.querySelectorAll('.btn-abrir-modal-parcela').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var grupo = btn.dataset.grupo;
            descEl.textContent = btn.dataset.descricao;
            listaEl.innerHTML = '<div class="text-center text-muted py-3">Carregando...</div>';
            modalParcelas.show();

            fetch('/Transacao/grupoParcela/<?= (int) $conta['id'] ?>?grupo=' + encodeURIComponent(grupo))
                .then(function (r) { return r.json(); })
                .then(function (resp) {
                    if (!resp.parcelas || !resp.parcelas.length) {
                        listaEl.innerHTML = '<div class="text-muted small">Nenhuma parcela encontrada.</div>';
                        return;
                    }
                    var html = '';
                    resp.parcelas.forEach(function (p) {
                        var valorFormatado = parseFloat(p.valor).toFixed(2).replace('.', ',');
                        html += '<div class="form-check">'
                            + '<input class="form-check-input" type="checkbox" name="ids[]" value="' + p.id + '" id="parcChk' + p.id + '" checked>'
                            + '<label class="form-check-label" for="parcChk' + p.id + '">'
                            + 'Parcela ' + p.parcela_atual + '/' + p.parcela_total
                            + ' — R$ ' + valorFormatado + ' (' + p.data_fato_gerador + ')'
                            + '</label></div>';
                    });
                    listaEl.innerHTML = html;
                })
                .catch(function () {
                    listaEl.innerHTML = '<div class="text-danger small">Erro ao carregar as parcelas. Tente novamente.</div>';
                });
        });
    });
});
</script>

<?php include __DIR__ . '/comuns/footer.php'; ?>