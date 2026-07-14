<?php
/** @var array $contato */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <h2 class="mb-4">Responder Contato</h2>

    <?= mensagens() ?>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($contato['assunto']) ?></h5>
            <h6 class="card-subtitle mb-2 text-muted">Enviado em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($contato['criado_em']))) ?></h6>
            <p><?= nl2br(htmlspecialchars($contato['mensagem'])) ?></p>
            <p class="small text-muted">De: <?= htmlspecialchars($contato['nome']) ?> &lt;<?= htmlspecialchars($contato['email']) ?>&gt;</p>
            <?php if (!empty($contato['resposta'])): ?>
                <hr>
                <div class="alert alert-info">
                    <strong>Resposta enviada:</strong>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($contato['resposta'])) ?></p>
                    <p class="small text-muted mb-0">Respondido em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($contato['respondido_em']))) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-body">
            <form action="/Admin/responder/<?= (int) $contato['id'] ?>" method="POST">
                <?= \App\Library\Csrf::getHiddenField() ?>
                <div class="mb-3">
                    <label for="resposta" class="form-label">Resposta</label>
                    <textarea id="resposta" name="resposta" class="form-control" rows="5" required><?= htmlspecialchars($contato['resposta'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Enviar resposta</button>
                <a href="/Admin/contatos" class="btn btn-secondary ms-2">Voltar</a>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>