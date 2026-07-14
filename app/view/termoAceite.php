<?php
/** @var array $termos */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <?= mensagens() ?>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Aceite de Termos</h3>
                </div>
                <div class="card-body">
                    <p>Para continuar usando o sistema, é preciso aceitar os termos abaixo.</p>

                    <form action="/Termo/aceitar" method="POST">
                        <?= \App\Library\Csrf::getHiddenField() ?>

                        <?php foreach ($termos as $index => $termo): ?>
                            <?php $excerpt = strip_tags($termo['conteudo']); ?>
                            <?php if (mb_strlen($excerpt) > 320): ?>
                                <?php $excerpt = mb_substr($excerpt, 0, 320) . '...'; ?>
                            <?php endif; ?>

                            <div class="mb-4">
                                <h5><?= htmlspecialchars($termo['titulo']) ?></h5>
                                <p class="text-muted">Tipo: <?= htmlspecialchars($termo['tipo']) ?> • Versão: <?= htmlspecialchars($termo['versao'] ?? '-') ?></p>
                                <div class="border rounded p-3 mb-2 termo-conteudo">
                                    <p class="mb-0 text-truncate" style="max-height: 6.5rem; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical;">
                                        <?= htmlspecialchars($excerpt) ?>
                                    </p>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" value="<?= (int) $termo['id'] ?>" id="termoAceito<?= (int) $termo['id'] ?>" name="termo_ids[]" required>
                                    <label class="form-check-label" for="termoAceito<?= (int) $termo['id'] ?>">
                                        Li e aceito este termo
                                    </label>
                                </div>
                                <button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#termoModal<?= (int) $termo['id'] ?>">
                                    Ver termo completo
                                </button>
                            </div>

                            <div class="modal fade" id="termoModal<?= (int) $termo['id'] ?>" tabindex="-1" aria-labelledby="termoModalLabel<?= (int) $termo['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="termoModalLabel<?= (int) $termo['id'] ?>"><?= htmlspecialchars($termo['titulo']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?= $termo['conteudo'] ?>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="d-flex justify-content-center">
                            <button type="submit" class="btn btn-success btn-lg">Aceitar termos e continuar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
