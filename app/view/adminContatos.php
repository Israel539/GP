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
                <?php $itemClass = 'list-group-item' . (empty($contato['resposta']) ? ' list-group-item-warning' : ''); ?>
                <?php if (!empty($contato['status']) && $contato['status'] === 'excluido'): ?>
                    <?php $itemClass .= ' list-group-item-danger'; ?>
                <?php endif; ?>
                <div class="<?= $itemClass ?> d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <a href="/Admin/verContato/<?= (int) $contato['id'] ?>" class="text-decoration-none text-body">
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
                                    <?php if (!empty($contato['excluido_por_admin'])): ?>
                                        <span class="badge bg-secondary ms-2">Excluído pelo admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary ms-2">Excluído pelo usuário</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </small>
                        </a>
                    </div>
                    <?php if (!empty($contato['resposta']) && (($contato['status'] ?? '') !== 'excluido')): ?>
                        <form action="/Admin/excluirContato/<?= (int) $contato['id'] ?>" method="POST" class="ms-3" onsubmit="return confirm('Confirma apagar esta conversa respondida?');">
                            <?= \App\Library\Csrf::getHiddenField() ?>
                            <button type="submit" class="btn btn-sm btn-danger">Apagar</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>