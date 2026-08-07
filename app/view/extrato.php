<?php
/**
 * @var array $conta
 * @var array $transacoes
 * @var float $saldoAtual
 * @var array $categorias
 * @var array $cartoes
 * @var array $tags
 * @var array $filtros
 */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container-fluid py-4">

    <div class="row mb-3 align-items-center">
        <div class="col-md-6">
            <h2><?= htmlspecialchars($conta['nome']) ?></h2>
            <span class="text-muted"><?= htmlspecialchars(ucfirst($conta['tipo'])) ?></span>
        </div>
        <div class="col-md-6 text-end">
            <div class="fs-3 <?= $saldoAtual < 0 ? 'text-danger' : 'text-success' ?>">
                R$ <?= number_format($saldoAtual, 2, ',', '.') ?>
            </div>
            <span class="small text-muted">Saldo calculado em tempo real (RN08)</span>
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
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">De</label>
                        <input type="date" name="data_inicio" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($filtros['data_inicio'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Ate</label>
                        <input type="date" name="data_fim" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($filtros['data_fim'] ?? '') ?>">
                    </div>
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
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100">Filtrar</button>
                    </div>
                </div>
                
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
                <?php foreach ($transacoes as $t): ?>
                    <?php $imutavel = $t['origem'] === 'api_openfinance'; ?>
                    <div class="card mb-2">
                        <div class="card-body py-2">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <strong><?= htmlspecialchars($t['descricao']) ?></strong>
                                    <div class="small text-muted">
                                        <?= htmlspecialchars($t['data_fato_gerador']) ?>
                                        &middot; <?= htmlspecialchars($t['modalidade']) ?>
                                        <?php if ($imutavel): ?>
                                            <span class="badge bg-secondary" title="Importado via Open Finance -- RN07: so categoria/tags editaveis">🔒 API</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <span class="<?= $t['tipo'] === 'despesa' ? 'text-danger' : 'text-success' ?>">
                                        <?= $t['tipo'] === 'despesa' ? '-' : '+' ?> R$ <?= number_format((float) $t['valor'], 2, ',', '.') ?>
                                    </span>
                                </div>
                                <div class="col-md-5">
                                    <form action="/Transacao/atualizarCategoria/<?= (int) $t['id'] ?>" method="POST" class="d-flex gap-1">
                                        <?= \App\Library\Csrf::getHiddenField() ?>
                                        <select name="categoria_id" class="form-select form-select-sm">
                                            <option value="">-- sem categoria --</option>
                                            <?php foreach ($categorias as $cat): ?>
                                                <option value="<?= (int) $cat['id'] ?>"
                                                    <?= ((int) ($t['categoria_id'] ?? 0) === (int) $cat['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cat['nome']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" name="tags" class="form-control form-control-sm"
                                            placeholder="tags..."
                                            value="<?= htmlspecialchars(implode(', ', array_column($t['tags'], 'nome'))) ?>">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">Salvar</button>
                                    </form>
                                </div>
                                <div class="col-md-2 text-end">
                                    <?php if (!$imutavel): ?>
                                        <a href="/Transacao/editar/<?= (int) $t['id'] ?>" class="btn btn-sm btn-outline-secondary me-1">Editar</a>
                                        <form action="/Transacao/excluir/<?= (int) $t['id'] ?>" method="POST" class="d-inline">
                                            <?= \App\Library\Csrf::getHiddenField() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Excluir esta transacao?')">Excluir</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
function alternarCampoCartao() {
    var modalidade = document.getElementById('modalidade').value;
    document.getElementById('campoCartao').style.display = (modalidade === 'credito') ? 'block' : 'none';
}
</script>

<?php include __DIR__ . '/comuns/footer.php'; ?>
