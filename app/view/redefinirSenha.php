<?php
/** @var string $token */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <h3 class="mb-3">Criar nova senha</h3>

            <?= mensagens() ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="/Login/salvarNovaSenha" method="POST">
                        <?= \App\Library\Csrf::getHiddenField() ?>
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                        <div class="mb-3">
                            <label class="form-label">Nova senha</label>
                            <input type="password" name="senha" class="form-control" required minlength="6" autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirmar nova senha</label>
                            <input type="password" name="confirmar_senha" class="form-control" required minlength="6">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Salvar nova senha</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
