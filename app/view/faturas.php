<?php include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <h2 class="mb-1"><?= htmlspecialchars($cartao['nome']) ?></h2>
    <p class="text-muted mb-4">Fecha dia <?= (int) $cartao['dia_fechamento'] ?>, vence dia <?= (int) $cartao['dia_vencimento'] ?></p>

    <?= mensagens() ?>

    <?php if (empty($faturas)): ?>
        <div class="alert alert-secondary">
            Nenhuma fatura ainda -- ela e criada automaticamente na primeira compra no credito lancada neste cartao.
        </div>
    <?php else: ?>
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Mes de referencia</th>
                    <th>Valor total</th>
                    <th>Vencimento</th>
                    <th>Status</th>
                    <th class="text-end">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($faturas as $f): ?>
                    <tr>
                        <td><?= htmlspecialchars(date('m/Y', strtotime($f['mes_referencia']))) ?></td>
                        <td>R$ <?= number_format((float) $f['valor_total'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($f['data_vencimento']))) ?></td>
                        <td>
                            <?php
                                $badge = ['aberta' => 'bg-info', 'fechada' => 'bg-warning text-dark', 'paga' => 'bg-success'][$f['status']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $badge ?>"><?= htmlspecialchars(ucfirst($f['status'])) ?></span>
                        </td>
                        <td class="text-end">
                            <a href="/Cartao/faturaDetalhe/<?= (int) $f['id'] ?>" class="btn btn-sm btn-outline-secondary">Detalhes</a>
                            <?php if ($f['status'] !== 'paga'): ?>
                                <form action="/Cartao/pagarFatura/<?= (int) $f['id'] ?>" method="POST" class="d-inline">
                                    <?= \App\Library\Csrf::getHiddenField() ?>
                                    <button type="submit" class="btn btn-sm btn-success"
                                        onclick="return confirm('Pagar esta fatura? Isso vai debitar o valor da conta pagadora agora.')">
                                        Pagar
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="/Cartao" class="btn btn-secondary">Voltar</a>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
