<?php
/** @var array $historico */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <h2 class="mb-4">Histórico de acesso de suporte</h2>
    <p class="text-muted">Todo acesso concedido a um recurso privado, por quem, e o motivo declarado. Isso nunca é apagado.</p>

    <?= mensagens() ?>

    <?php if (empty($historico)): ?>
        <div class="alert alert-secondary">Nenhum acesso de suporte foi concedido ainda.</div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Quando</th>
                    <th>Admin</th>
                    <th>Recurso</th>
                    <th>Usuário afetado</th>
                    <th>Motivo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historico as $h): ?>
                    <tr>
                        <td><?= htmlspecialchars($h['concedido_em']) ?></td>
                        <td><?= htmlspecialchars($h['admin_nome']) ?></td>
                        <td><?= htmlspecialchars($h['tipo_recurso']) ?> #<?= (int) $h['recurso_id'] ?></td>
                        <td><?= htmlspecialchars($h['alvo_nome'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($h['motivo']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
