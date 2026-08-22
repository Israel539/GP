<?php
/**
 * @var array $contas
 * @var array $usuario
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

    <!-- Widget opcional: dinheiro fisico + saldo em conta (RN08) + total.
         Desligado por padrao -- so aparece se o usuario ativar a checkbox
         (preferencia salva, ver migracao 012). -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center <?= !empty($usuario['exibir_saldo_dinheiro']) ? 'mb-3' : '' ?>">
                <strong>Resumo financeiro</strong>
                <form action="/Conta/alternarExibirSaldoDinheiro" method="POST" class="d-flex align-items-center gap-2 mb-0">
                    <?= \App\Library\Csrf::getHiddenField() ?>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="chkExibirDinheiro"
                            name="exibir" value="1" onchange="this.form.submit()"
                            <?= !empty($usuario['exibir_saldo_dinheiro']) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="chkExibirDinheiro">Mostrar dinheiro físico</label>
                    </div>
                </form>
            </div>

            <?php if (!empty($usuario['exibir_saldo_dinheiro'])): ?>
                <div class="row text-center g-3">
                    <div class="col-md-4 border-end">
                        <div class="small text-muted mb-1">Dinheiro físico</div>
                        <form action="/Conta/atualizarSaldoDinheiro" method="POST"
                            class="d-flex justify-content-center align-items-center gap-1">
                            <?= \App\Library\Csrf::getHiddenField() ?>
                            <span class="small">R$</span>
                            <input type="text" name="saldo_dinheiro" class="form-control form-control-sm text-center"
                                style="max-width: 110px;"
                                value="<?= number_format((float) $usuario['saldo_dinheiro'], 2, ',', '') ?>">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Salvar</button>
                        </form>
                    </div>
                    <div class="col-md-4 border-end">
                        <div class="small text-muted mb-1">Saldo em conta</div>
                        <div class="fs-5 <?= $saldoContasTotal < 0 ? 'text-danger' : 'text-success' ?>">
                            R$ <?= number_format($saldoContasTotal, 2, ',', '.') ?>
                        </div>
                        <div class="small text-muted">soma de todas as contas, calculado (RN08)</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted mb-1">Total</div>
                        <?php $total = $saldoContasTotal + (float) $usuario['saldo_dinheiro']; ?>
                        <div class="fs-4 fw-bold <?= $total < 0 ? 'text-danger' : 'text-success' ?>">
                            R$ <?= number_format($total, 2, ',', '.') ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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
                        <h5 class="card-title"><?= htmlspecialchars($conta['nome']) ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted">
                            <?= htmlspecialchars(ucfirst($conta['tipo'])) ?>
                        </h6>
                        <p class="fs-4 <?= $conta['saldo_atual'] < 0 ? 'text-danger' : 'text-success' ?>">
                            R$ <?= number_format((float) $conta['saldo_atual'], 2, ',', '.') ?>
                        </p>
                        <a href="/Transacao/extrato/<?= (int) $conta['id'] ?>" class="btn btn-outline-primary btn-sm">Ver extrato</a>
                        <a href="/Conta/form/<?= (int) $conta['id'] ?>" class="btn btn-outline-secondary btn-sm">Editar</a>
                        <form action="/Conta/excluir/<?= (int) $conta['id'] ?>" method="POST" class="d-inline"
                            onsubmit="return confirm('Excluir a conta &quot;<?= htmlspecialchars(addslashes($conta['nome'])) ?>&quot;? Isso apaga também TODAS as transações dela, para sempre. Essa ação não pode ser desfeita.')">
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
