<?php
/** @var array $contatos */
/** @var array $usuario */
/** @var int $excluidosCount */
include __DIR__ . '/comuns/header.php';

$usuario = $usuario ?? ['nome' => '', 'email' => '']; ?>

<div class="container py-5">
    <?= mensagens() ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-custom shadow-lg mb-4">
                <div class="card-header card-header-custom text-center py-4">
                    <h3 class="mb-0"><i class="bi bi-envelope-at me-2"></i>Entre em Contato</h3>
                    <p class="mb-0 mt-2 opacity-75">Envie sua mensagem ao administrador ou tire uma duvida sobre o sistema.</p>
                </div>

                <div class="card-body p-4">
                    <form action="/Contato/enviar" method="POST">
                        <?= \App\Library\Csrf::getHiddenField() ?>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="nome_contato" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" id="nome_contato" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="email_contato" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email_contato" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="assunto" class="form-label">Assunto</label>
                            <input type="text" class="form-control" id="assunto" name="assunto" placeholder="Ex: Duvida sobre o sistema" required>
                        </div>

                        <div class="mb-3">
                            <label for="mensagem" class="form-label">Sua Mensagem</label>
                            <textarea class="form-control" id="mensagem" name="mensagem" rows="5" placeholder="Escreva aqui detalhadamente o que você precisa..." required></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="/Home" class="btn btn-secondary">Voltar</a>
                            <button type="submit" class="btn btn-primary">Enviar Mensagem</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card card-custom shadow-lg">
                <div class="card-header card-header-custom d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h3 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Historico de contato</h3>
                        <p class="mb-0 mt-2 opacity-75">Veja as mensagens que você enviou e as respostas do administrador.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if (!empty($contatos)): ?>
                            <form action="/Contato/limparHistorico" method="POST" onsubmit="return confirm('Tem certeza que deseja limpar todo o historico de contato? Esta acao nao pode ser desfeita.');">
                                <?= \App\Library\Csrf::getHiddenField() ?>
                                <button type="submit" class="btn btn-outline-danger">Limpar histórico</button>
                            </form>
                        <?php endif; ?>

                        <?php if (!empty($excluidosCount) && $excluidosCount > 0): ?>
                            <form action="/Contato/restaurarHistorico" method="POST" onsubmit="return confirm('Restaurar historico excluido?');">
                                <?= \App\Library\Csrf::getHiddenField() ?>
                                <button type="submit" class="btn btn-outline-success">Restaurar histórico (<?= (int) $excluidosCount ?>)</button>
                            </form>
                            <div class="d-flex align-items-center ms-2">
                                <small class="text-muted">Restauração disponível por <?= (int) RESTORE_WINDOW_HOURS ?>h após exclusão.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($contatos)): ?>
                        <p class="text-muted">Nenhuma mensagem enviada ainda.</p>
                    <?php else: ?>
                        <?php foreach ($contatos as $contato): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="card-title mb-1"><?= htmlspecialchars($contato['assunto']) ?></h5>
                                            <small class="text-muted">Enviado em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($contato['criado_em']))) ?></small>
                                        </div>
                                        <span class="badge <?= empty($contato['resposta']) ? 'bg-warning text-dark' : 'bg-success' ?>">
                                            <?= empty($contato['resposta']) ? 'Pendente' : 'Respondido' ?>
                                        </span>
                                    </div>
                                    <p><?= nl2br(htmlspecialchars($contato['mensagem'])) ?></p>

                                    <?php if (!empty($contato['resposta'])): ?>
                                        <div class="alert alert-info mb-0">
                                            <strong>Resposta do administrador:</strong>
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($contato['resposta'])) ?></p>
                                            <p class="small text-muted mb-0">Respondido em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($contato['respondido_em']))) ?></p>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning mb-0">Aguarde a resposta do administrador.</div>
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