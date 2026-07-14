<?php
/** @var array $plano */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <?php if (!empty($plano['imagem_url'])): ?>
                    <img src="<?= htmlspecialchars($plano['imagem_url']) ?>" class="card-img-top" alt="Imagem do produto">
                <?php endif; ?>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h3 class="card-title"><?= htmlspecialchars($plano['nome']) ?></h3>
                            <p class="text-muted mb-1">Status: <strong><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $plano['status']))) ?></strong></p>
                            <?php if (!empty($plano['data_prevista_compra'])): ?>
                                <p class="text-muted mb-0">Compra prevista para <?= htmlspecialchars(date('d/m/Y', strtotime($plano['data_prevista_compra']))) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="text-end">
                            <a href="/PlanoCompra" class="btn btn-secondary btn-sm">Voltar</a>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <h6>Valor total</h6>
                                <p class="fs-4 mb-0">R$ <?= number_format((float) $plano['valor_total'], 2, ',', '.') ?></p>
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