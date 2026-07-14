<?php
/**
 * @var array $contatos
 * @var bool $mostrarExcluidos
 */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Mensagens de Contato</h2>
            <?php if (!empty($mostrarExcluidos)): ?>
                <small class="text-muted">Exibindo contatos excluídos.</small>
            <?php endif; ?>
        </div>
        <div>
            <?php if (!empty($mostrarExcluidos)): ?>
                <form action="/Admin/restaurarContatos" method="POST" class="d-inline">
                    <?= \App\Library\Csrf::getHiddenField() ?>
                    <button type="submit" class="btn btn-success me-2">Restaurar todos</button>
                </form>
                <a href="/Admin/contatos" class="btn btn-outline-secondary">Voltar</a>
            <?php else: ?>
                <a href="/Admin/contatos?show=excluidos" class="btn btn-outline-secondary">Ver excluídos</a>
            <?php endif; ?>
        </div>
    </div>

    <?= mensagens() ?>

    <?php if (empty($contatos)): ?>
        <div class="alert alert-secondary">Nenhuma mensagem de contato recebida.</div>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($contatos as $contato): ?>
                <?php $itemClass = 'list-group-item list-group-item-action' . (empty($contato['resposta']) ? ' list-group-item-warning' : ''); ?>
                <?php if (!empty($contato['status']) && $contato['status'] === 'excluido'): ?>
                    <?php $itemClass .= ' list-group-item-danger'; ?>
                <?php endif; ?>
                <a href="/Admin/verContato/<?= (int) $contato['id'] ?>" class="<?= $itemClass ?>">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1"><?= htmlspecialchars($contato['assunto']) ?></h5>
                        <small><?= htmlspecialchars(date('d/m/Y H:i', strtotime($contato['criado_em']))) ?></small>
                    </div>
                    <p class="mb-1 text-truncate"><?= htmlspecialchars($contato['mensagem']) ?></p>
                    <small>
                        De: <?= htmlspecialchars($contato['nome']) ?> &lt;<?= htmlspecialchars($contato['email']) ?>&gt;
                        <?= empty($contato['resposta']) ? '(Pendente)' : '(Respondido)' ?>
                        <?php if (!empty($contato['status']) && $contato['status'] === 'excluido'): ?>
                            <span class="badge bg-danger ms-2">Excluído</span>
                        <?php endif; ?>
                    </small>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>