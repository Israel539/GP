<?php
/** @var array $projetos */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <h2 class="mb-4">Todos os projetos</h2>

    <?= mensagens() ?>

    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Dono</th>
                <th>Status</th>
                <th class="text-end">Acoes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($projetos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['nome']) ?></td>
                    <td><?= htmlspecialchars($p['dono_nome']) ?></td>
                    <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $p['status']))) ?></td>
                    <td class="text-end">
                        <a href="/Projeto/kanban/<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-primary">Abrir quadro</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
