<?php include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">

    <div class="row mb-4">
        <div class="col-8">
            <h2>Meus Cartoes</h2>
        </div>
        <div class="col-4 text-end">
            <a href="/Cartao/form" class="btn btn-primary">+ Novo cartao</a>
        </div>
    </div>

    <?= mensagens() ?>

    <?php if (empty($cartoes)): ?>
        <div class="alert alert-secondary">Voce ainda nao cadastrou nenhum cartao.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($cartoes as $cartao): ?>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($cartao['nome']) ?></h5>
                            <p class="text-muted mb-1">Fecha dia <?= (int) $cartao['dia_fechamento'] ?>, vence dia <?= (int) $cartao['dia_vencimento'] ?></p>
                            <?php if (!empty($cartao['limite'])): ?>
                                <p class="text-muted">Limite: R$ <?= number_format((float) $cartao['limite'], 2, ',', '.') ?></p>
                            <?php endif; ?>
                            <a href="/Cartao/faturas/<?= (int) $cartao['id'] ?>" class="btn btn-outline-primary btn-sm">Ver faturas</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
