<?php include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-lg">
                <div class="card-body p-4">
                    <h3 class="mb-4">Novo cartao de credito</h3>

                    <?= mensagens() ?>

                    <?php if (empty($contas)): ?>
                        <div class="alert alert-warning">
                            Voce precisa ter ao menos uma conta cadastrada para vincular um cartao.
                            <a href="/Conta/form">Criar conta agora</a>.
                        </div>
                    <?php else: ?>
                        <form action="/Cartao/salvar" method="POST">
                            <?= \App\Library\Csrf::getHiddenField() ?>

                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome do cartao</label>
                                <input type="text" class="form-control" id="nome" name="nome"
                                    value="<?= valorAntigo('nome') ?>" placeholder="Ex: Nubank Ultravioleta" required>
                                <?= campoErro('nome') ?>
                            </div>

                            <div class="mb-3">
                                <label for="conta_pagadora_id" class="form-label">Conta que paga a fatura</label>
                                <select class="form-select" id="conta_pagadora_id" name="conta_pagadora_id" required>
                                    <?php foreach ($contas as $conta): ?>
                                        <option value="<?= (int) $conta['id'] ?>"><?= htmlspecialchars($conta['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="limite" class="form-label">Limite (opcional)</label>
                                <input type="text" class="form-control" id="limite" name="limite"
                                    value="<?= valorAntigo('limite') ?>" placeholder="0,00">
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="dia_fechamento" class="form-label">Dia de fechamento</label>
                                    <input type="number" min="1" max="31" class="form-control" id="dia_fechamento"
                                        name="dia_fechamento" value="<?= valorAntigo('dia_fechamento') ?>" required>
                                    <?= campoErro('dia_fechamento') ?>
                                </div>
                                <div class="col-md-6">
                                    <label for="dia_vencimento" class="form-label">Dia de vencimento</label>
                                    <input type="number" min="1" max="31" class="form-control" id="dia_vencimento"
                                        name="dia_vencimento" value="<?= valorAntigo('dia_vencimento') ?>" required>
                                    <?= campoErro('dia_vencimento') ?>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="/Cartao" class="btn btn-secondary">Voltar</a>
                                <button type="submit" class="btn btn-primary">Cadastrar cartao</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
