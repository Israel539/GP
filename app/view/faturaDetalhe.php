<?php include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <h2 class="mb-1">Fatura <?= htmlspecialchars(date('m/Y', strtotime($fatura['mes_referencia']))) ?></h2>
    <p class="text-muted mb-4">
        Vencimento: <?= htmlspecialchars(date('d/m/Y', strtotime($fatura['data_vencimento']))) ?>
        &middot; Total: R$ <?= number_format((float) $fatura['valor_total'], 2, ',', '.') ?>
    </p>

    <?= mensagens() ?>

    <?php if (empty($transacoes)): ?>
        <div class="alert alert-secondary">Nenhuma transacao nesta fatura.</div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descricao</th>
                    <th>Categoria</th>
                    <th class="text-end">Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transacoes as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($t['data_fato_gerador']))) ?></td>
                        <td><?= htmlspecialchars($t['descricao']) ?></td>
                        <td><?= htmlspecialchars($t['categoria_nome'] ?? '-') ?></td>
                        <td class="text-end">R$ <?= number_format((float) $t['valor'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="/Cartao/faturas/<?= (int) $fatura['cartao_id'] ?>" class="btn btn-secondary">Voltar</a>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
