<?php
/**
 * @var array $projetos
 * @var array $contas
 * @var array $cartoes
 * @var array $compromissos
 * @var array $planosCompra
 * @var array $minhasSolicitacoes
 */
include __DIR__ . '/comuns/header.php';

$rotulosStatus = [
    'pendente'  => ['texto' => 'Aguardando', 'classe' => 'text-bg-warning'],
    'atendida'  => ['texto' => 'Atendida', 'classe' => 'text-bg-success'],
    'cancelada' => ['texto' => 'Cancelada', 'classe' => 'text-bg-secondary'],
];

$rotulosTipo = [
    'projeto'      => 'Projeto',
    'conta'        => 'Conta',
    'cartao'       => 'Cartão',
    'compromisso'  => 'Compromisso',
    'plano_compra' => 'Plano de compra',
];
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <a href="/Usuario/perfil" class="btn btn-outline-secondary btn-sm mb-3">
                <i class="bi bi-arrow-left"></i> Voltar para o perfil
            </a>

            <?= mensagens() ?>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-headset"></i> Solicitar suporte
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        Descreva onde você está com dificuldade e um administrador vai analisar o pedido.
                        Quando ele conceder o acesso, uma caixa de chat aparece automaticamente para vocês conversarem.
                    </p>

                    <?php
                    $temAlgumRecurso = !empty($projetos) || !empty($contas) || !empty($cartoes) || !empty($compromissos) || !empty($planosCompra);
                    ?>

                    <?php if (!$temAlgumRecurso): ?>
                        <div class="alert alert-secondary small mb-0">
                            Você ainda não tem nenhum projeto, conta, cartão, compromisso ou plano de compra
                            cadastrado para solicitar suporte sobre eles.
                        </div>
                    <?php else: ?>
                        <form action="/SolicitacaoSuporte/enviar" method="POST">
                            <?= \App\Library\Csrf::getHiddenField() ?>

                            <div class="mb-3">
                                <label class="form-label">Sobre o que é o problema?</label>
                                <select name="tipo_recurso" id="ssTipoRecurso" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    <?php if (!empty($projetos)): ?>
                                        <option value="projeto">Projeto (kanban)</option>
                                    <?php endif; ?>
                                    <?php if (!empty($contas)): ?>
                                        <option value="conta">Conta (extrato)</option>
                                    <?php endif; ?>
                                    <?php if (!empty($cartoes)): ?>
                                        <option value="cartao">Cartão de crédito</option>
                                    <?php endif; ?>
                                    <?php if (!empty($compromissos)): ?>
                                        <option value="compromisso">Compromisso (agenda)</option>
                                    <?php endif; ?>
                                    <?php if (!empty($planosCompra)): ?>
                                        <option value="plano_compra">Plano de compra</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Qual, exatamente?</label>

                                <?php if (!empty($projetos)): ?>
                                    <select class="form-select ss-recurso-select d-none" data-tipo="projeto">
                                        <option value="">Selecione o projeto...</option>
                                        <?php foreach ($projetos as $p): ?>
                                            <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>

                                <?php if (!empty($contas)): ?>
                                    <select class="form-select ss-recurso-select d-none" data-tipo="conta">
                                        <option value="">Selecione a conta...</option>
                                        <?php foreach ($contas as $c): ?>
                                            <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>

                                <?php if (!empty($cartoes)): ?>
                                    <select class="form-select ss-recurso-select d-none" data-tipo="cartao">
                                        <option value="">Selecione o cartão...</option>
                                        <?php foreach ($cartoes as $c): ?>
                                            <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>

                                <?php if (!empty($compromissos)): ?>
                                    <select class="form-select ss-recurso-select d-none" data-tipo="compromisso">
                                        <option value="">Selecione o compromisso...</option>
                                        <?php foreach ($compromissos as $c): ?>
                                            <option value="<?= (int) $c['id'] ?>">
                                                <?= htmlspecialchars($c['titulo']) ?> — <?= htmlspecialchars(date('d/m/Y', strtotime($c['data_inicio']))) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>

                                <?php if (!empty($planosCompra)): ?>
                                    <select class="form-select ss-recurso-select d-none" data-tipo="plano_compra">
                                        <option value="">Selecione o plano de compra...</option>
                                        <?php foreach ($planosCompra as $p): ?>
                                            <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>

                                <input type="hidden" name="recurso_id" id="ssRecursoId">
                                <div class="form-text" id="ssRecursoDica">Escolha primeiro o tipo acima.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Descreva o problema (mínimo 10 caracteres)</label>
                                <textarea name="mensagem" class="form-control" rows="4" required minlength="10"
                                    placeholder="Ex: o saldo dessa conta não está batendo com o extrato do banco"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Enviar pedido de suporte
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($minhasSolicitacoes)): ?>
                <div class="card">
                    <div class="card-header">Meus pedidos de suporte</div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($minhasSolicitacoes as $s): ?>
                            <?php $statusInfo = $rotulosStatus[$s['status']] ?? ['texto' => $s['status'], 'classe' => 'text-bg-secondary']; ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="badge <?= $statusInfo['classe'] ?>"><?= $statusInfo['texto'] ?></span>
                                        <strong class="ms-1"><?= htmlspecialchars($rotulosTipo[$s['tipo_recurso']] ?? $s['tipo_recurso']) ?></strong>
                                        <div class="small text-muted mt-1"><?= htmlspecialchars($s['mensagem']) ?></div>
                                        <div class="small text-muted">
                                            Pedido em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($s['criado_em']))) ?>
                                            <?php if ($s['status'] === 'atendida' && !empty($s['atendido_em'])): ?>
                                                &middot; atendido em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($s['atendido_em']))) ?>
                                                por <?= htmlspecialchars($s['admin_nome'] ?? 'um administrador') ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($s['status'] === 'pendente'): ?>
                                        <form action="/SolicitacaoSuporte/cancelar/<?= (int) $s['id'] ?>" method="POST"
                                            onsubmit="return confirm('Cancelar esse pedido de suporte?')">
                                            <?= \App\Library\Csrf::getHiddenField() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Cancelar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
    (function () {
        var tipoSelect = document.getElementById('ssTipoRecurso');
        var recursoSelects = document.querySelectorAll('.ss-recurso-select');
        var recursoIdInput = document.getElementById('ssRecursoId');
        var dica = document.getElementById('ssRecursoDica');

        function mostrarSelectDoTipo(tipo) {
            recursoSelects.forEach(function (sel) {
                if (sel.dataset.tipo === tipo) {
                    sel.classList.remove('d-none');
                    sel.required = true;
                } else {
                    sel.classList.add('d-none');
                    sel.required = false;
                    sel.value = '';
                }
            });
            recursoIdInput.value = '';
            dica.textContent = tipo ? 'Escolha o item da lista.' : 'Escolha primeiro o tipo acima.';
        }

        tipoSelect.addEventListener('change', function () {
            mostrarSelectDoTipo(this.value);
        });

        recursoSelects.forEach(function (sel) {
            sel.addEventListener('change', function () {
                recursoIdInput.value = this.value;
            });
        });

        mostrarSelectDoTipo(tipoSelect.value);
    })();
</script>

<?php include __DIR__ . '/comuns/footer.php'; ?>
