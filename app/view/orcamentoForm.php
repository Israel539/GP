<?php
/** @var array $categorias */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <h3 class="mb-4">Novo orçamento</h3>

            <?= mensagens() ?>

            <?php if (empty($categorias)): ?>
                <div class="alert alert-warning">
                    Você precisa ter ao menos uma categoria de despesa cadastrada.
                    <a href="/Categoria/form">Criar categoria agora</a>.
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form action="/Orcamento/salvar" method="POST">
                            <?= \App\Library\Csrf::getHiddenField() ?>

                            <div class="mb-3">
                                <label class="form-label">Categoria</label>
                                <select name="categoria_id" class="form-select" required>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= (int) $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= campoErro('categoria_id') ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Limite mensal (R$)</label>
                                <input type="text" name="valor_limite" class="form-control" placeholder="500,00" required>
                                <?= campoErro('valor_limite') ?>
                                <div class="form-text">Se já existir um orçamento pra essa categoria, o limite é atualizado.</div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="/Orcamento" class="btn btn-secondary">Voltar</a>
                                <button type="submit" class="btn btn-primary">Salvar</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
