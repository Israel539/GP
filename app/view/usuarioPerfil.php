<?php
/** @var array $usuario */
include __DIR__ . '/comuns/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <h2 class="mb-0">Meu Perfil</h2>
                        <a href="/Usuario/editar" class="btn btn-primary btn-sm">
                            <i class="bi bi-pencil"></i> Editar perfil
                        </a>
                    </div>

                    <?= mensagens() ?>

                    <div class="d-flex align-items-center mb-4">
                        <?php if (!empty($usuario['foto'])): ?>
                            <img src="<?= htmlspecialchars($usuario['foto']) ?>" alt="Foto de perfil"
                                class="rounded-circle me-3" style="width: 96px; height: 96px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary bg-opacity-25 d-flex align-items-center justify-content-center me-3"
                                style="width: 96px; height: 96px;">
                                <i class="bi bi-person-fill" style="font-size: 2.5rem;"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <div class="fs-4"><?= htmlspecialchars($usuario['nome']) ?></div>
                            <div class="text-muted"><?= htmlspecialchars($usuario['email']) ?></div>
                        </div>
                    </div>

                    <dl class="row">
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
