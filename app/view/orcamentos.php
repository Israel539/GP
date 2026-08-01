<?php
/** @var array $orcamentos */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-8">
            <h2>Orçamento por Categoria</h2>
            <p class="text-muted small mb-0">Defina um limite mensal por categoria e acompanhe o gasto do mês.</p>
        </div>
        <div class="col-4 text-end">
            <a href="/Orcamento/form" class="btn btn-primary">+ Novo orçamento</a>
        </div>
    </div>

    <?= mensagens() ?>

    <?php if (empty($orcamentos)): ?>
        <div class="alert alert-secondary">Você ainda não definiu nenhum orçamento por categoria.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($orcamentos as $o): ?>
                <?php
                    $gasto = (float) $o['gasto_mes'];
                    $limite = (float) $o['valor_limite'];
                    $percentual = $limite > 0 ? min(100, ($gasto / $limite) * 100) : 0;
                    $estourou = $gasto > $limite;
                ?>
                <div class="col-md-6">
                    <div class="card h-100 <?= $estourou ? 'border-danger' : '' ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0">
                                    <span class="badge" style="background-color: <?= htmlspecialchars($o['categoria_cor'] ?? '#6c757d') ?>">&nbsp;</span>
                                    <?= htmlspecialchars($o['categoria_nome']) ?>
                                </h5>
                                <form action="/Orcamento/excluir/<?= (int) $o['id'] ?>" method="POST" class="d-inline">
                                    <?= \App\Library\Csrf::getHiddenField() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Remover este orçamento?')">Remover</button>
                                </form>
                            </div>

                            <?php if ($estourou): ?>
                                <div class="alert alert-danger py-1 px-2 small mb-2">
                                    <i class="bi bi-exclamation-triangle-fill"></i> Orçamento estourado!
                                </div>
                            <?php endif; ?>

                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar <?= $estourou ? 'bg-danger' : 'bg-success' ?>" style="width: <?= $percentual ?>%"></div>
                            </div>
                            <div class="small text-muted mt-1">
                                R$ <?= number_format($gasto, 2, ',', '.') ?> de R$ <?= number_format($limite, 2, ',', '.') ?> este mês
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
