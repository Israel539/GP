<?php
/** @var array $categorias */
/** @var int $meuId */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-8">
            <h2>Categorias</h2>
            <p class="text-muted small mb-0">As categorias sem "excluir" são padrão do sistema, disponíveis pra todo mundo.</p>
        </div>
        <div class="col-4 text-end">
            <a href="/Categoria/form" class="btn btn-primary">+ Nova categoria</a>
        </div>
    </div>

    <?= mensagens() ?>

    <div class="row g-2">
        <?php foreach (['receita' => 'Receitas', 'despesa' => 'Despesas'] as $tipo => $rotulo): ?>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><?= $rotulo ?></div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($categorias as $c): ?>
                            <?php if ($c['tipo'] !== $tipo) continue; ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    <span class="badge" style="background-color: <?= htmlspecialchars($c['cor'] ?? '#6c757d') ?>">&nbsp;</span>
                                    <?= htmlspecialchars($c['nome']) ?>
                                    <?php if ($c['usuario_id'] === null): ?>
                                        <span class="badge bg-light text-muted border">padrão</span>
                                    <?php endif; ?>
                                </span>
                                <?php if ((int) ($c['usuario_id'] ?? 0) === $meuId): ?>
                                    <form action="/Categoria/excluir/<?= (int) $c['id'] ?>" method="POST" class="d-inline">
                                        <?= \App\Library\Csrf::getHiddenField() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Excluir esta categoria?')">Excluir</button>
                                    </form>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
