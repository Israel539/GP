<?php include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-lg">
                <div class="card-body p-4">
                    <h3 class="mb-4">Nova conta</h3>

                    <?= mensagens() ?>

                    <form action="/Conta/salvar" method="POST">
                        <?= \App\Library\Csrf::getHiddenField() ?>

                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="nome" name="nome"
                                value="<?= valorAntigo('nome') ?>" placeholder="Ex: Nubank, Carteira" required>
                            <?= campoErro('nome') ?>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tipo" class="form-label">Tipo</label>
                                <select class="form-select" id="tipo" name="tipo">
                                    <option value="corrente">Conta corrente</option>
                                    <option value="poupanca">Poupanca</option>
                                    <option value="carteira">Carteira</option>
                                    <option value="investimento">Investimento</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="saldo_inicial" class="form-label">Saldo inicial</label>
                                <input type="text" class="form-control" id="saldo_inicial" name="saldo_inicial"
                                    value="<?= valorAntigo('saldo_inicial', '0,00') ?>" placeholder="0,00">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="instituicao" class="form-label">Instituicao (opcional)</label>
                            <input type="text" class="form-control" id="instituicao" name="instituicao"
                                value="<?= valorAntigo('instituicao') ?>" placeholder="Ex: Banco Inter">
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="/Conta" class="btn btn-secondary">Voltar</a>
                            <button type="submit" class="btn btn-primary">Criar conta</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
