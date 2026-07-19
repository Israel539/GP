<?php
/** @var array $usuarioAlvo */
/** @var array $projetos */
/** @var array $contas */
/** @var array $cartoes */
/** @var array $compromissos */
/** @var array $planosCompra */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <h2 class="mb-1">Recursos de <?= htmlspecialchars($usuarioAlvo['nome']) ?></h2>
    <p class="text-muted mb-4">
        <?= htmlspecialchars($usuarioAlvo['email']) ?> &middot;
        Aqui só aparecem IDs e status -- nome, saldo, título e conteúdo continuam
        escondidos até você pedir acesso de suporte de verdade.
    </p>

    <?= mensagens() ?>

    <?php
        $secoes = [
            'projeto'      => ['titulo' => 'Projetos',        'itens' => $projetos,     'campo' => 'papel'],
            'conta'        => ['titulo' => 'Contas',          'itens' => $contas,       'campo' => 'tipo'],
            'cartao'       => ['titulo' => 'Cartões',         'itens' => $cartoes,      'campo' => null],
            'compromisso'  => ['titulo' => 'Compromissos',    'itens' => $compromissos, 'campo' => 'tipo'],
            'plano_compra' => ['titulo' => 'Planos de compra','itens' => $planosCompra, 'campo' => 'status'],
        ];
    ?>

    <div class="row g-3">
        <?php foreach ($secoes as $tipoRecurso => $secao): ?>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><?= $secao['titulo'] ?> (<?= count($secao['itens']) ?>)</div>
                    <?php if (empty($secao['itens'])): ?>
                        <div class="card-body">
                            <p class="text-muted mb-0 small">Nenhum registro.</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($secao['itens'] as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        #<?= (int) $item['id'] ?>
                                        <?php if ($secao['campo'] && !empty($item[$secao['campo']])): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars(str_replace('_', ' ', $item[$secao['campo']])) ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <a class="btn btn-sm btn-outline-warning"
                                        href="/Admin/suporte?tipo_recurso=<?= $tipoRecurso ?>&recurso_id=<?= (int) $item['id'] ?>">
                                        Pedir acesso
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <a href="/Admin/usuarios" class="btn btn-secondary mt-4">Voltar</a>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
