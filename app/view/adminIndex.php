<?php include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <h2 class="mb-4">Painel do Administrador</h2>

    <?= mensagens() ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h1><?= (int) $totalUsuarios ?></h1>
                    <p class="text-muted mb-0">Usuarios cadastrados</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h1><?= (int) $totalProjetos ?></h1>
                    <p class="text-muted mb-0">Projetos no sistema</p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="/Admin/usuarios" class="btn btn-primary">Gerenciar usuarios</a>
        <a href="/Admin/projetos" class="btn btn-outline-primary">Ver todos os projetos</a>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
