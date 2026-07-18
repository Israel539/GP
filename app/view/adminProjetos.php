<?php
/** @var int $totalProjetos */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <h2 class="mb-4">Projetos do sistema</h2>

    <?= mensagens() ?>

    <div class="alert alert-info">
        Por privacidade, o admin nao ve nome nem conteudo de projetos individuais aqui --
        so o total agregado. Para inspecionar um projeto especifico com justificativa
        registrada em log, use <a href="/Admin/suporte">Acesso de suporte</a>.
    </div>

    <div class="card text-center" style="max-width: 300px;">
        <div class="card-body">
            <h1><?= (int) $totalProjetos ?></h1>
            <p class="text-muted mb-0">Projetos no sistema</p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
