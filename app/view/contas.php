<?php
/** @var array $contas */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">

    <div class="row mb-4">
        <div class="col-8">
            <h2>Minhas Contas</h2>
            <?php if (!empty($isAdmin)): ?>
                <span class="badge bg-warning text-dark">Visao de administrador: todas as contas do sistema</span>
            <?php endif; ?>
        </div>
        <div class="col-4 text-end">
            <a href="/PlanoCompra" class="btn btn-outline-primary me-2">Planejar compra</a>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($contas as $conta): ?>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($conta['nome']) ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted">
                            <?= htmlspecialchars(ucfirst($conta['tipo'])) ?>
                            <?php if (!empty($conta['dono_nome'])): ?>
                                &middot; <?= htmlspecialchars($conta['dono_nome']) ?>
                            <?php endif; ?>
                        </h6>
                        <p class="fs-4 <?= $conta['saldo_atual'] < 0 ? 'text-danger' : 'text-success' ?>">
                            R$ <?= number_format((float) $conta['saldo_atual'], 2, ',', '.') ?>
                        </p>
                        <a href="/Transacao/extrato/<?= (int) $conta['id'] ?>" class="btn btn-outline-primary btn-sm">Ver extrato</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
