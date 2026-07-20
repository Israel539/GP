<?php include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <h3 class="mb-3">Esqueci minha senha</h3>
            <p class="text-muted">Informe seu e-mail cadastrado. Se ele existir no sistema, enviamos um link para você criar uma nova senha.</p>

            <?= mensagens() ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="/Login/enviarLinkReset" method="POST">
                        <?= \App\Library\Csrf::getHiddenField() ?>
                        <div class="mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" class="form-control" placeholder="seu@email.com" required autofocus>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="/Login" class="btn btn-secondary">Voltar</a>
                            <button type="submit" class="btn btn-primary">Enviar link</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
