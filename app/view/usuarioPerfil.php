<?php
/** @var array $usuario */
include __DIR__ . '/comuns/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h2 class="mb-4">Meu Perfil</h2>

                    <?= mensagens() ?>

                    <dl class="row">
                        <dt class="col-sm-3">Nome</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($usuario['nome']) ?></dd>

                        <dt class="col-sm-3">E-mail</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($usuario['email']) ?></dd>

                        <?php if (!empty($usuario['cpf'])): ?>
                            <dt class="col-sm-3">CPF</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($usuario['cpf']) ?></dd>
                        <?php endif; ?>

                        <?php if (!empty($usuario['data_nascimento'])): ?>
                            <dt class="col-sm-3">Data de nascimento</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars(date('d/m/Y', strtotime($usuario['data_nascimento']))) ?></dd>
                        <?php endif; ?>

                        <?php if (!empty($usuario['telefone_whats'])): ?>
                            <dt class="col-sm-3">WhatsApp</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($usuario['telefone_whats']) ?></dd>
                        <?php endif; ?>
                    </dl>

                    <div class="mt-4">
                        <form action="/Usuario/excluirConta" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir sua conta? Esta ação não pode ser desfeita.');">
                            <?= \App\Library\Csrf::getHiddenField() ?>
                            <button type="submit" class="btn btn-danger">Excluir minha conta</button>
                            <a href="/Home" class="btn btn-secondary ms-2">Voltar</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
