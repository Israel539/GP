<?php include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <h2 class="mb-4">Termos e Políticas</h2>

    <?php if (empty($termos)): ?>
        <div class="alert alert-warning">Nenhum termo ativo encontrado no momento.</div>
    <?php else: ?>
        <?php foreach ($termos as $termo): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h4 class="mb-0"><?= htmlspecialchars($termo['titulo']) ?></h4>
                    <small class="text-muted">Tipo: <?= htmlspecialchars($termo['tipo']) ?> • Versão: <?= htmlspecialchars($termo['versao'] ?? '-') ?></small>
                </div>
                <div class="card-body">
                    <?= $termo['conteudo'] ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>