<?php
/** @var array|null $conta */
$editando = !empty($conta);
include __DIR__ . '/comuns/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-lg">
                <div class="card-body p-4">
                    <h3 class="mb-4"><?= $editando ? 'Editar conta' : 'Nova conta' ?></h3>

                    <?= mensagens() ?>

                    <form action="<?= $editando ? '/Conta/atualizar/' . (int) $conta['id'] : '/Conta/salvar' ?>" method="POST">
                        <?= \App\Library\Csrf::getHiddenField() ?>

                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="nome" name="nome"
                                value="<?= valorAntigo('nome', $conta['nome'] ?? '') ?>" placeholder="Ex: Nubank, Carteira" required>
                            <?= campoErro('nome') ?>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tipo" class="form-label">Tipo</label>
                                <?php $tipoAtual = valorAntigo('tipo', $conta['tipo'] ?? 'corrente'); ?>
                                <select class="form-select" id="tipo" name="tipo">
                                    <option value="corrente" <?= $tipoAtual === 'corrente' ? 'selected' : '' ?>>Conta corrente</option>
                                    <option value="poupanca" <?= $tipoAtual === 'poupanca' ? 'selected' : '' ?>>Poupanca</option>
                                    <option value="carteira" <?= $tipoAtual === 'carteira' ? 'selected' : '' ?>>Carteira</option>
                                    <option value="investimento" <?= $tipoAtual === 'investimento' ? 'selected' : '' ?>>Investimento</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="saldo_inicial" class="form-label">Saldo inicial</label>
                                <input type="text" class="form-control" id="saldo_inicial" name="saldo_inicial"
                                    value="<?= valorAntigo('saldo_inicial', $editando ? number_format((float) $conta['saldo_inicial'], 2, ',', '') : '0,00') ?>" placeholder="0,00">
                                <?php if ($editando): ?>
                                    <div class="form-text">Mudar isso desloca o saldo atual (RN08 continua calculando: saldo inicial + transações).</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="instituicao" class="form-label">Instituicao (opcional)</label>
                            <input type="text" class="form-control" id="instituicao" name="instituicao"
                                value="<?= valorAntigo('instituicao', $conta['instituicao'] ?? '') ?>" placeholder="Ex: Banco Inter">
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="/Conta" class="btn btn-secondary">Voltar</a>
                            <button type="submit" class="btn btn-primary"><?= $editando ? 'Salvar alterações' : 'Criar conta' ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
