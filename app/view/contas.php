<?php
/**
 * @var array $contas
 * @var array|null $contaDinheiro
 * @var float $saldoContasTotal
 */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">

    <div class="row mb-4">
        <div class="col-8">
            <h2>Minhas Contas</h2>
        </div>
        <div class="col-4 text-end">
            <a href="/Conta/form" class="btn btn-primary me-2">
                <i class="bi bi-plus-lg"></i> Nova conta
            </a>
            <a href="/PlanoCompra" class="btn btn-outline-primary me-2">Planejar compra</a>
        </div>
    </div>

    <?= mensagens() ?>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center g-3">
                <div class="col-md-8">
                    <strong class="d-block mb-1">Dinheiro Físico</strong>
                    <?php if (empty($contas)): ?>
                        <span class="small text-muted">Cadastre uma conta primeiro pra poder escolher.</span>
                    <?php else: ?>
                        <form action="/Conta/definirContaDinheiro" method="POST" class="d-flex align-items-center gap-2">
                            <?= \App\Library\Csrf::getHiddenField() ?>
                            <span class="small text-muted">Lançamentos em "Dinheiro" descontam de:</span>
                            <select name="conta_id" class="form-select form-select-sm" style="max-width: 220px;" onchange="this.form.submit()">
                                <option value="">-- escolha uma conta --</option>
                                <?php foreach ($contas as $conta): ?>
                                    <option value="<?= (int) $conta['id'] ?>" <?= (!empty($contaDinheiro) && (int) $contaDinheiro['id'] === (int) $conta['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($conta['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="small text-muted mb-1">Saldo total (todas as contas)</div>
                    <div class="fs-5 fw-bold <?= $saldoContasTotal < 0 ? 'text-danger' : 'text-success' ?>">
                        R$ <?= number_format($saldoContasTotal, 2, ',', '.') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($contas)): ?>
        <div class="alert alert-secondary">Voce ainda nao cadastrou nenhuma conta.</div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($contas as $conta): ?>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <?= htmlspecialchars($conta['nome']) ?>
                            <?php if (!empty($contaDinheiro) && (int) $contaDinheiro['id'] === (int) $conta['id']): ?>
                                <span class="badge bg-secondary-subtle text-secondary-emphasis fs-6">Dinheiro Físico</span>
                            <?php endif; ?>
                        </h5>
                        <h6 class="card-subtitle mb-2 text-muted">
                            <?= htmlspecialchars(ucfirst($conta['tipo'])) ?>
                        </h6>
                        <p class="fs-4 <?= $conta['saldo_atual'] < 0 ? 'text-danger' : 'text-success' ?>">
                            R$ <?= number_format((float) $conta['saldo_atual'], 2, ',', '.') ?>
                        </p>
                        <a href="/Transacao/extrato/<?= (int) $conta['id'] ?>" class="btn btn-outline-primary btn-sm">Ver extrato</a>
                        <a href="/Conta/form/<?= (int) $conta['id'] ?>" class="btn btn-outline-secondary btn-sm">Editar</a>
                        <form action="/Conta/excluir/<?= (int) $conta['id'] ?>" method="POST" class="d-inline"
                            onsubmit="return confirm('Excluir a conta &quot;<?= htmlspecialchars(addslashes($conta['nome'])) ?>&quot;? Isso apaga também TODAS as transações dela, para sempre.<?= (!empty($contaDinheiro) && (int) $contaDinheiro['id'] === (int) $conta['id']) ? ' Essa e sua conta configurada como Dinheiro Fisico -- vai precisar escolher outra depois.' : '' ?> Essa ação não pode ser desfeita.')">
                            <?= \App\Library\Csrf::getHiddenField() ?>
                            <button type="submit" class="btn btn-outline-danger btn-sm">Excluir</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
