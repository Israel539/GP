<?php
/** @var array $pendentes */
include __DIR__ . '/comuns/header.php';

$rotulosTipo = [
    'projeto'      => 'Projeto (kanban)',
    'conta'        => 'Conta (extrato)',
    'cartao'       => 'Cartão (faturas)',
    'compromisso'  => 'Compromisso (agenda)',
    'plano_compra' => 'Plano de compra',
];
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Solicitações de suporte</h2>
        <a href="/Admin/suporte" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-plus-lg"></i> Conceder acesso manualmente
        </a>
    </div>
    <p class="text-muted">
        Pedidos abertos pelos próprios usuários, indicando onde precisam de ajuda. Ao clicar em
        "Atender", o formulário de acesso já vem preenchido com o recurso pedido.
    </p>

    <?= mensagens() ?>

    <?php if (empty($pendentes)): ?>
        <div class="alert alert-secondary">Nenhum pedido de suporte pendente no momento.</div>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($pendentes as $s): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong><?= htmlspecialchars($s['usuario_nome']) ?></strong>
                            <span class="badge text-bg-light border ms-1">
                                <?= htmlspecialchars($rotulosTipo[$s['tipo_recurso']] ?? $s['tipo_recurso']) ?> #<?= (int) $s['recurso_id'] ?>
                            </span>
                            <div class="mt-1"><?= htmlspecialchars($s['mensagem']) ?></div>
                            <div class="small text-muted mt-1">
                                Pedido em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($s['criado_em']))) ?>
                            </div>
                        </div>
                        <a class="btn btn-warning btn-sm text-nowrap"
                            href="/Admin/suporte?tipo_recurso=<?= urlencode($s['tipo_recurso']) ?>&recurso_id=<?= (int) $s['recurso_id'] ?>">
                            Atender
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
