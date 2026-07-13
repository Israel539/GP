<?php include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-lg">
                <div class="card-body p-4">
                    <h3 class="mb-4">Novo projeto</h3>

                    <?= mensagens() ?>

                    <form action="/Projeto/salvar" method="POST">
                        <?= \App\Library\Csrf::getHiddenField() ?>

                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome do projeto</label>
                            <input type="text" class="form-control" id="nome" name="nome"
                                value="<?= valorAntigo('nome') ?>" required>
                            <?= campoErro('nome') ?>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descricao</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= valorAntigo('descricao') ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="data_inicio" class="form-label">Inicio previsto</label>
                                <input type="date" class="form-control" id="data_inicio" name="data_inicio"
                                    value="<?= valorAntigo('data_inicio') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="data_entrega" class="form-label">Entrega prevista</label>
                                <input type="date" class="form-control" id="data_entrega" name="data_entrega"
                                    value="<?= valorAntigo('data_entrega') ?>">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="/Projeto" class="btn btn-secondary">Voltar</a>
                            <button type="submit" class="btn btn-primary">Criar projeto</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
