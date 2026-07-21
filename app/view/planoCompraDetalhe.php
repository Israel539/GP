<?php
/** @var array $plano */
/** @var array|null $planoPai */
/** @var array $filhos */
/** @var bool $temFilhos */
/** @var array $parcelas */
/** @var float $valorGuardado */
/** @var float $valorRestante */
/** @var float $valorTotalExibido */
/** @var float $progresso */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-start gap-3">
                            <?php if (!empty($plano['imagem_url'])): ?>
                                <img src="<?= htmlspecialchars($plano['imagem_url']) ?>" class="plano-img-thumb flex-shrink-0" alt="Imagem do produto">
                            <?php endif; ?>
                            <div>
                                <?php if (!empty($planoPai)): ?>
                                    <p class="mb-1 small">
                                        <a href="/PlanoCompra/ver/<?= (int) $planoPai['id'] ?>">&larr; <?= htmlspecialchars($planoPai['nome']) ?></a>
                                    </p>
                                <?php endif; ?>
                                <h3 class="card-title mb-0"><?= htmlspecialchars($plano['nome']) ?></h3>
                                <p class="text-muted mb-1">Status: <strong><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $plano['status']))) ?></strong></p>
                                <?php if (!empty($plano['data_prevista_compra'])): ?>
                                    <p class="text-muted mb-0">Compra prevista para <?= htmlspecialchars(date('d/m/Y', strtotime($plano['data_prevista_compra']))) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-end">
                            <a href="<?= !empty($planoPai) ? '/PlanoCompra/ver/' . (int) $planoPai['id'] : '/PlanoCompra' ?>" class="btn btn-secondary btn-sm">Voltar</a>
                        </div>
                    </div>

                    <?= mensagens() ?>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Guardado: <strong class="text-success">R$ <?= number_format($valorGuardado, 2, ',', '.') ?></strong></span>
                            <span>Meta: R$ <?= number_format($valorTotalExibido, 2, ',', '.') ?></span>
                        </div>
                        <div class="progress" style="height: 14px;">
                            <div class="progress-bar bg-success" style="width: <?= $progresso ?>%"><?= number_format($progresso, 0) ?>%</div>
                        </div>
                        <div class="small text-muted mt-1">
                            Faltam R$ <?= number_format($valorRestante, 2, ',', '.') ?>
                            <?php if (!$temFilhos): ?>
                                &middot; <?= count($parcelas) ?> de <?= (int) $plano['parcelas_previstas'] ?> parcela(s) prevista(s)
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($temFilhos): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Itens em <?= htmlspecialchars($plano['nome']) ?></h5>
                            <a href="/PlanoCompra/form?parent_id=<?= (int) $plano['id'] ?>" class="btn btn-sm btn-primary">+ Adicionar item</a>
                        </div>
                        <div class="row g-3 mb-4">
                            <?php foreach ($filhos as $filho): ?>
                                <?php
                                    $valorTotalFilho = (float) $filho['valor_total'];
                                    $valorGuardadoFilho = (float) $filho['valor_guardado'];
                                    $progressoFilho = $valorTotalFilho > 0 ? min(100, ($valorGuardadoFilho / $valorTotalFilho) * 100) : 0;
                                ?>
                                <div class="col-md-6">
                                    <a href="/PlanoCompra/ver/<?= (int) $filho['id'] ?>" class="text-decoration-none text-body">
                                        <div class="border rounded p-3 h-100">
                                            <div class="d-flex justify-content-between">
                                                <strong><?= htmlspecialchars($filho['nome']) ?></strong>
                                                <?php if (!empty($filho['total_filhos'])): ?>
                                                    <span class="badge bg-secondary"><?= (int) $filho['total_filhos'] ?> item(s)</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="progress mt-2" style="height: 8px;">
                                                <div class="progress-bar bg-success" style="width: <?= $progressoFilho ?>%"></div>
                                            </div>
                                            <div class="small text-muted mt-1">
                                                R$ <?= number_format($valorGuardadoFilho, 2, ',', '.') ?> de R$ <?= number_format($valorTotalFilho, 2, ',', '.') ?>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="mb-4">
                            <a href="/PlanoCompra/form?parent_id=<?= (int) $plano['id'] ?>" class="btn btn-sm btn-outline-primary">+ Transformar em categoria (adicionar item)</a>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <h6>Valor total</h6>
                                <p class="fs-4 mb-0">R$ <?= number_format($valorTotalExibido, 2, ',', '.') ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <h6>Parcelas previstas</h6>
                                <p class="fs-4 mb-0"><?= (int) $plano['parcelas_previstas'] ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <h6>Data de conclusão</h6>
                                <p class="fs-5 mb-0"><?= !empty($plano['data_conclusao']) ? htmlspecialchars(date('d/m/Y', strtotime($plano['data_conclusao']))) : '---' ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if (!in_array($plano['status'], ['concluido', 'cancelado'], true)): ?>
                        <div class="card mb-4">
                            <div class="card-header">Guardar uma parcela</div>
                            <div class="card-body">
                                <form action="/PlanoCompra/adicionarParcela/<?= (int) $plano['id'] ?>" method="POST" class="row g-2 align-items-end">
                                    <?= \App\Library\Csrf::getHiddenField() ?>
                                    <div class="col-md-3">
                                        <label class="form-label small">Valor</label>
                                        <input type="text" name="valor" class="form-control" placeholder="0,00" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Data</label>
                                        <input type="date" name="data_pagamento" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Observação (opcional)</label>
                                        <input type="text" name="observacao" class="form-control" placeholder="Ex: parcela 1 de 4">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-success w-100">Adicionar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($parcelas)): ?>
                        <h5 class="mb-3">Parcelas guardadas</h5>
                        <ul class="list-group mb-4">
                            <?php foreach ($parcelas as $p): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="text-success">R$ <?= number_format((float) $p['valor'], 2, ',', '.') ?></strong>
                                        <span class="text-muted">&middot; <?= htmlspecialchars(date('d/m/Y', strtotime($p['data_pagamento']))) ?></span>
                                        <?php if (!empty($p['observacao'])): ?>
                                            <span class="text-muted">&middot; <?= htmlspecialchars($p['observacao']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!in_array($plano['status'], ['concluido', 'cancelado'], true)): ?>
                                        <form action="/PlanoCompra/excluirParcela/<?= (int) $p['id'] ?>" method="POST" class="d-inline">
                                            <?= \App\Library\Csrf::getHiddenField() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Remover esta parcela?')">Remover</button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (!empty($plano['produto_url'])): ?>
                        <p><strong>Link do produto:</strong> <a href="<?= htmlspecialchars($plano['produto_url']) ?>" target="_blank"><?= htmlspecialchars($plano['produto_url']) ?></a></p>
                    <?php endif; ?>

                    <p><?= nl2br(htmlspecialchars($plano['descricao'] ?? 'Nenhuma descrição informada.')) ?></p>

                    <div class="d-flex gap-2 mt-4">
                        <?php if ($plano['status'] !== 'concluido' && $plano['status'] !== 'cancelado'): ?>
                            <form action="/PlanoCompra/concluir/<?= (int) $plano['id'] ?>" method="POST">
                                <?= \App\Library\Csrf::getHiddenField() ?>
                                <button type="submit" class="btn btn-success">Marcar como concluído</button>
                            </form>
                            <form action="/PlanoCompra/cancelar/<?= (int) $plano['id'] ?>" method="POST">
                                <?= \App\Library\Csrf::getHiddenField() ?>
                                <button type="submit" class="btn btn-danger">Cancelar plano</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>