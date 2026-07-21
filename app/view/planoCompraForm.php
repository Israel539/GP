<?php include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="mb-1"><?php if (!empty($plano)): ?>Editar Plano de Compra<?php else: ?>Novo Plano de Compra<?php endif; ?></h3>
                    <?php if (!empty($planoPai)): ?>
                        <p class="text-muted mb-4">Dentro de <strong><?= htmlspecialchars($planoPai['nome']) ?></strong></p>
                    <?php endif; ?>

                    <?= mensagens() ?>

                    <form action="<?php if (!empty($plano)): ?>/PlanoCompra/atualizar<?php else: ?>/PlanoCompra/salvar<?php endif; ?>" method="POST">
                        <?= \App\Library\Csrf::getHiddenField() ?>

                        <?php if (!empty($plano)): ?>
                            <input type="hidden" name="id" value="<?= (int) $plano['id'] ?>">
                        <?php elseif (!empty($planoPai)): ?>
                            <input type="hidden" name="parent_id" value="<?= (int) $planoPai['id'] ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome do plano</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($plano['nome'] ?? valorAntigo('nome')) ?>" required>
                            <?= campoErro('nome') ?>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= htmlspecialchars($plano['descricao'] ?? valorAntigo('descricao')) ?></textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="valor_total" class="form-label">Valor total</label>
                                <input type="text" class="form-control" id="valor_total" name="valor_total" value="<?= htmlspecialchars($plano['valor_total'] ?? valorAntigo('valor_total')) ?>" required>
                                <?= campoErro('valor_total') ?>
                            </div>
                            <div class="col-md-6">
                                <label for="parcelas_previstas" class="form-label">Parcelas previstas</label>
                                <input type="number" class="form-control" id="parcelas_previstas" name="parcelas_previstas" min="1" value="<?= (int) ($plano['parcelas_previstas'] ?? valorAntigo('parcelas_previstas', '1')) ?>" required>
                                <?= campoErro('parcelas_previstas') ?>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="data_prevista_compra" class="form-label">Data prevista de compra</label>
                                <input type="date" class="form-control" id="data_prevista_compra" name="data_prevista_compra" value="<?= htmlspecialchars($plano['data_prevista_compra'] ?? valorAntigo('data_prevista_compra')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="imagem_url" class="form-label">URL da imagem do produto</label>
                                <input type="url" class="form-control" id="imagem_url" name="imagem_url" value="<?= htmlspecialchars($plano['imagem_url'] ?? valorAntigo('imagem_url')) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="produto_url" class="form-label">Link do produto</label>
                            <input type="url" class="form-control" id="produto_url" name="produto_url" value="<?= htmlspecialchars($plano['produto_url'] ?? valorAntigo('produto_url')) ?>">
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?= !empty($planoPai) ? '/PlanoCompra/ver/' . (int) $planoPai['id'] : '/PlanoCompra' ?>" class="btn btn-secondary">Voltar</a>
                            <button type="submit" class="btn btn-primary">Salvar plano</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>