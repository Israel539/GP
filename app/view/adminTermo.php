<?php
/** @var array $termo */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <h2 class="mb-4">Visualizar Termo</h2>

    <?= mensagens() ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <h3><?= htmlspecialchars($termo['titulo']) ?></h3>
            <p class="text-muted">Tipo: <?= htmlspecialchars($termo['tipo']) ?> • Versão: <?= htmlspecialchars($termo['versao'] ?? '-') ?> • <?= $termo['ativo'] ? 'Ativo' : 'Inativo' ?></p>
            <hr>
            <div class="term-content">
                <?= $termo['conteudo'] ?>
            </div>
            <div class="mt-4">
                <a href="/Admin/termos" class="btn btn-secondary">Voltar</a>
                <?php if (!$termo['ativo']): ?>
                    <form action="/Admin/ativarTermo/<?= (int) $termo['id'] ?>" method="POST" class="d-inline">
                        <?= \App\Library\Csrf::getHiddenField() ?>
                        <button type="submit" class="btn btn-primary">Ativar</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
