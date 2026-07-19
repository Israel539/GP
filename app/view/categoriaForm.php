<?php include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <h3 class="mb-4">Nova categoria</h3>

            <?= mensagens() ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="/Categoria/salvar" method="POST">
                        <?= \App\Library\Csrf::getHiddenField() ?>

                        <div class="mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text" name="nome" class="form-control" value="<?= valorAntigo('nome') ?>" required>
                            <?= campoErro('nome') ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <select name="tipo" class="form-select" required>
                                <option value="despesa">Despesa</option>
                                <option value="receita">Receita</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cor</label>
                            <input type="color" name="cor" class="form-control form-control-color" value="#6c757d">
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="/Categoria" class="btn btn-secondary">Voltar</a>
                            <button type="submit" class="btn btn-primary">Criar categoria</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
