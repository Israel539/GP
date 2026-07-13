<?php include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <h2 class="mb-4">Mensagens de Contato</h2>

    <?= mensagens() ?>

    <?php if (empty($contatos)): ?>
        <div class="alert alert-secondary">Nenhuma mensagem de contato recebida.</div>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($contatos as $contato): ?>
                <a href="/Admin/verContato/<?= (int) $contato['id'] ?>" class="list-group-item list-group-item-action<?= empty($contato['resposta']) ? ' list-group-item-warning' : '' ?>">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1"><?= htmlspecialchars($contato['assunto']) ?></h5>
                        <small><?= htmlspecialchars(date('d/m/Y H:i', strtotime($contato['criado_em']))) ?></small>
                    </div>
                    <p class="mb-1 text-truncate"><?= htmlspecialchars($contato['mensagem']) ?></p>
                    <small>De: <?= htmlspecialchars($contato['nome']) ?> &lt;<?= htmlspecialchars($contato['email']) ?>&gt; <?= empty($contato['resposta']) ? '(Pendente)' : '(Respondido)' ?></small>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>