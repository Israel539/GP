<?php include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <h2 class="mb-4">Usuarios</h2>

    <?= mensagens() ?>

    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Nivel</th>
                <th>Status</th>
                <th class="text-end">Acoes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($u['nome']) ?>
                        <a href="/Admin/usuarioRecursos/<?= (int) $u['id'] ?>" class="small ms-1">(ver recursos)</a>
                    </td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <?php if ((int) $u['nivel'] === $NIVEL_ADMIN): ?>
                            <span class="badge bg-warning text-dark">Admin</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Comum</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int) $u['statusRegistro'] === 1): ?>
                            <span class="badge bg-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Bloqueado</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php if ((int) $u['id'] === (int) $meuId): ?>
                            <span class="text-muted small">voce</span>
                        <?php else: ?>
                            <?php if ((int) $u['statusRegistro'] === 1): ?>
                                <form action="/Admin/bloquear/<?= (int) $u['id'] ?>" method="POST" class="d-inline">
                                    <?= \App\Library\Csrf::getHiddenField() ?>
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Bloquear este usuario?')">Bloquear</button>
                                </form>
                            <?php else: ?>
                                <form action="/Admin/ativar/<?= (int) $u['id'] ?>" method="POST" class="d-inline">
                                    <?= \App\Library\Csrf::getHiddenField() ?>
                                    <button class="btn btn-sm btn-outline-success">Ativar</button>
                                </form>
                            <?php endif; ?>

                            <?php if ((int) $u['nivel'] === $NIVEL_ADMIN): ?>
                                <form action="/Admin/rebaixar/<?= (int) $u['id'] ?>" method="POST" class="d-inline">
                                    <?= \App\Library\Csrf::getHiddenField() ?>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="return confirm('Remover privilegios de admin?')">Rebaixar</button>
                                </form>
                            <?php else: ?>
                                <form action="/Admin/promover/<?= (int) $u['id'] ?>" method="POST" class="d-inline">
                                    <?= \App\Library\Csrf::getHiddenField() ?>
                                    <button class="btn btn-sm btn-outline-warning" onclick="return confirm('Tornar este usuario admin?')">Promover</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
