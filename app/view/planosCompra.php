<?php
/**
 * @var array $planos
 * @var bool $mostrarExcluidos
 * @var int $excluidosCount
 * @var string $search
 * @var int $page
 * @var int $totalPages
 */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-8">
            <h2>Planos de Compra</h2>
            <p class="text-muted">Organize suas metas de compra, acompanhe o valor total e o número de parcelas previstas.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="/PlanoCompra/form" class="btn btn-primary">+ Novo Plano</a>
            <?php if (!empty($excluidosCount) && empty($mostrarExcluidos)): ?>
                <a href="/PlanoCompra?show=excluidos" class="btn btn-outline-secondary ms-2">Excluídos (<?= (int) $excluidosCount ?>)</a>
            <?php endif; ?>
            <?php if (!empty($mostrarExcluidos)): ?>
                <form action="/PlanoCompra/restaurarTodos" method="POST" class="d-inline">
                    <?= \App\Library\Csrf::getHiddenField() ?>
                    <button type="submit" class="btn btn-success ms-2">Restaurar todos</button>
                </form>
                <a href="/PlanoCompra" class="btn btn-outline-secondary ms-2">Voltar</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-8">
            <form method="GET" action="/PlanoCompra" class="row g-2 align-items-center">
                <div class="col-auto">
                    <input type="text" name="search" class="form-control" placeholder="Buscar planos..." value="<?= htmlspecialchars($search ?? '') ?>">
                </div>
                <?php if (!empty($mostrarExcluidos)): ?>
                    <input type="hidden" name="show" value="excluidos">
                <?php endif; ?>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary">Buscar</button>
                </div>
            </form>
        </div>
    </div>

    <?= mensagens() ?>

    <?php if (empty($planos)): ?>
        <div class="alert alert-secondary">Nenhum plano de compra criado ainda.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($planos as $plano): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <?php if (!empty($plano['imagem_url'])): ?>
                                    <img src="<?= htmlspecialchars($plano['imagem_url']) ?>" class="plano-img-thumb flex-shrink-0" alt="Imagem do produto">
                                <?php endif; ?>
                                <h5 class="card-title mb-0">
                                    <?= htmlspecialchars($plano['nome']) ?>
                                    <?php if (!empty($plano['total_filhos'])): ?>
                                        <span class="badge bg-secondary"><?= (int) $plano['total_filhos'] ?> item(s)</span>
                                    <?php endif; ?>
                                </h5>
                            </div>

                            <?php
                                $valorGuardado = (float) ($plano['valor_guardado'] ?? 0);
                                $valorTotal = (float) $plano['valor_total'];
                                $parcelasPagas = (int) ($plano['parcelas_pagas'] ?? 0);
                                $progresso = $valorTotal > 0 ? min(100, ($valorGuardado / $valorTotal) * 100) : 0;
                                $valorRestante = max(0, $valorTotal - $valorGuardado);
                            ?>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>R$ <?= number_format($valorGuardado, 2, ',', '.') ?> guardado</span>
                                    <span>Meta R$ <?= number_format($valorTotal, 2, ',', '.') ?></span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: <?= $progresso ?>%"></div>
                                </div>
                                <div class="small text-muted mt-1">
                                    <?= $parcelasPagas ?> de <?= (int) $plano['parcelas_previstas'] ?> parcela(s)
                                    &middot; Faltam R$ <?= number_format($valorRestante, 2, ',', '.') ?>
                                </div>
                            </div>

                            <?php if (!empty($plano['produto_url'])): ?>
                                <p class="mb-2"><a href="<?= htmlspecialchars($plano['produto_url']) ?>" target="_blank">Ver produto</a></p>
                            <?php endif; ?>
                            <p class="card-text text-truncate"><?= nl2br(htmlspecialchars($plano['descricao'] ?? 'Sem descrição.')) ?></p>
                            <div class="mt-auto pt-3 d-flex justify-content-between align-items-center">
                                <span class="badge bg-<?= $plano['status'] === 'concluido' ? 'success' : ($plano['status'] === 'cancelado' ? 'danger' : ($plano['status'] === 'excluido' ? 'dark' : 'secondary')) ?>">
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $plano['status']))) ?>
                                </span>
                                <div class="btn-group">
                                    <?php if (!empty($mostrarExcluidos) && $plano['status'] === 'excluido'): ?>
                                        <form action="/PlanoCompra/restaurar/<?= (int) $plano['id'] ?>" method="POST" style="display:inline">
                                            <?= \App\Library\Csrf::getHiddenField() ?>
                                            <button type="submit" class="btn btn-outline-success btn-sm">Restaurar</button>
                                        </form>
                                    <?php else: ?>
                                        <a href="/PlanoCompra/ver/<?= (int) $plano['id'] ?>" class="btn btn-outline-primary btn-sm">Ver detalhes</a>
                                        <a href="/PlanoCompra/editar/<?= (int) $plano['id'] ?>" class="btn btn-outline-secondary btn-sm">Editar</a>
                                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-planoid="<?= (int) $plano['id'] ?>" data-planonome="<?= htmlspecialchars($plano['nome']) ?>">
                                            Excluir
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($totalPages) && $totalPages > 1): ?>
        <nav aria-label="Paginação de planos" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === ($page ?? 1) ? 'active' : '' ?>">
                        <a class="page-link" href="/PlanoCompra?search=<?= urlencode($search ?? '') ?>&page=<?= $i ?><?= !empty($mostrarExcluidos) ? '&show=excluidos' : '' ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o plano <strong id="deletePlanName"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deletePlanForm" method="POST" action="">
                    <?= \App\Library\Csrf::getHiddenField() ?>
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var deleteModal = document.getElementById('confirmDeleteModal');
        if (!deleteModal) {
            return;
        }

        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var planoId = button.getAttribute('data-planoid');
            var planoNome = button.getAttribute('data-planonome');
            var form = document.getElementById('deletePlanForm');
            var planNameEl = document.getElementById('deletePlanName');

            form.action = '/PlanoCompra/excluir/' + planoId;
            planNameEl.textContent = planoNome;
        });
    });
</script>

<?php include __DIR__ . '/comuns/footer.php'; ?>