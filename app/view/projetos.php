<?php include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">

    <div class="row mb-4">
        <div class="col-8">
            <h2>Meus Projetos</h2>
            <?php if (!empty($isAdmin)): ?>
                <span class="badge bg-warning text-dark">Visao de administrador: todos os projetos do sistema</span>
            <?php endif; ?>
        </div>
        <div class="col-4 text-end">
            <a href="/Projeto/form" class="btn btn-primary">+ Novo projeto</a>
        </div>
    </div>

    <?= mensagens() ?>

    <?php if (empty($projetos)): ?>
        <div class="alert alert-secondary">Voce ainda nao participa de nenhum projeto.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($projetos as $projeto): ?>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($projeto['nome']) ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $projeto['status']))) ?>
                                <?php if (!empty($projeto['papel'])): ?>
                                    &middot; <span class="badge bg-secondary"><?= htmlspecialchars($projeto['papel']) ?></span>
                                <?php endif; ?>
                            </h6>
                            <p class="card-text"><?= nl2br(htmlspecialchars($projeto['descricao'] ?? '')) ?></p>
                            <a href="/Projeto/kanban/<?= (int) $projeto['id'] ?>" class="btn btn-outline-primary btn-sm">Abrir quadro</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
