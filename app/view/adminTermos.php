<?php include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <h2 class="mb-4">Termos e Políticas</h2>

    <?= mensagens() ?>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Novo termo ou versão</strong>
                </div>
                <div class="card-body">
                    <form action="/Admin/salvarTermo" method="POST">
                        <?= \App\Library\Csrf::getHiddenField() ?>

                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select id="tipo" name="tipo" class="form-select" required>
                                <option value="termos_uso">Termos de Uso</option>
                                <option value="politica_privacidade">Política de Privacidade</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título</label>
                            <input type="text" id="titulo" name="titulo" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="versao" class="form-label">Versão</label>
                            <input type="text" id="versao" name="versao" class="form-control"
                                placeholder="Ex: v1.0 ou 2026-07-13">
                            <div class="form-text">Se vazio, será gerado automaticamente.</div>
                        </div>

                        <div class="mb-3">
                            <label for="conteudo" class="form-label">Conteúdo</label>
                            <textarea id="conteudo" name="conteudo" class="form-control" rows="8" required></textarea>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="ativo" name="ativo" checked>
                            <label class="form-check-label" for="ativo">Ativar imediatamente</label>
                        </div>

                        <button type="submit" class="btn btn-primary">Salvar termo</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Termos cadastrados</strong>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($termos)): ?>
                        <div class="list-group-item">Nenhum termo cadastrado ainda.</div>
                    <?php else: ?>
                        <?php foreach ($termos as $termo): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h5 class="mb-1"><?= htmlspecialchars($termo['titulo']) ?></h5>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($termo['tipo']) ?>
                                            • versão <?= htmlspecialchars($termo['versao'] ?? '-') ?>
                                            • <?= $termo['ativo'] ? 'Ativo' : 'Inativo' ?>
                                        </small>
                                    </div>
                                    <?php if ($termo['ativo']): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php endif; ?>
                                </div>
                                <p class="mb-2 text-truncate" style="max-height: 3.6rem; overflow: hidden;">
                                    <?= strip_tags($termo['conteudo']) ?>
                                </p>
                                <div>
                                    <a href="/Admin/verTermo/<?= (int) $termo['id'] ?>" class="btn btn-sm btn-outline-secondary me-2">Visualizar</a>
                                    <?php if (!$termo['ativo']): ?>
                                        <form action="/Admin/ativarTermo/<?= (int) $termo['id'] ?>" method="POST" class="d-inline">
                                            <?= \App\Library\Csrf::getHiddenField() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Ativar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
