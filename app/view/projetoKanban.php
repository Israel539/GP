<?php include __DIR__ . '/comuns/header.php'; ?>

<div class="container-fluid py-4">

    <div class="row mb-3">
        <div class="col-8">
            <h2><?= htmlspecialchars($projeto['nome']) ?></h2>
            <span class="badge bg-info text-dark"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $projeto['status']))) ?></span>
        </div>
        <div class="col-4 text-end">
            <?php if ($projeto['status'] !== 'concluido'): ?>
                <form action="/Projeto/concluir/<?= (int) $projeto['id'] ?>" method="POST" class="d-inline">
                    <?= \App\Library\Csrf::getHiddenField() ?>
                    <button type="submit" class="btn btn-success btn-sm"
                        onclick="return confirm('Concluir este projeto? So funciona se nao houver tarefa pendente.')">
                        Concluir projeto
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?= mensagens() ?>

    <div class="row">
        <!-- Colaboradores + convite -->
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-header">Colaboradores</div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($colaboradores as $c): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <?= htmlspecialchars($c['nome']) ?>
                            <span class="badge bg-secondary"><?= htmlspecialchars($c['papel']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="card-body">
                    <form action="/Projeto/convidar/<?= (int) $projeto['id'] ?>" method="POST">
                        <?= \App\Library\Csrf::getHiddenField() ?>
                        <label class="form-label small">Convidar por e-mail</label>
                        <div class="input-group input-group-sm">
                            <input type="email" name="email_convidado" class="form-control" placeholder="amigo@email.com" required>
                            <button type="submit" class="btn btn-outline-primary">Convidar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Kanban -->
        <div class="col-md-9">

            <form action="/Tarefa/criar" method="POST" class="card card-body mb-3">
                <?= \App\Library\Csrf::getHiddenField() ?>
                <input type="hidden" name="projeto_id" value="<?= (int) $projeto['id'] ?>">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small">Nova tarefa</label>
                        <input type="text" name="titulo" class="form-control form-control-sm" placeholder="Titulo" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Responsavel</label>
                        <select name="responsavel_id" class="form-select form-select-sm">
                            <option value="">-- ninguem --</option>
                            <?php foreach ($colaboradores as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Prazo</label>
                        <input type="date" name="data_limite" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Adicionar</button>
                    </div>
                </div>
            </form>

            <div class="row g-3">
                <?php
                $colunas = [
                    'a_fazer'      => 'A Fazer',
                    'em_andamento' => 'Em Andamento',
                    'concluido'    => 'Concluido',
                ];
                ?>
                <?php foreach ($colunas as $statusColuna => $tituloColuna): ?>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header"><?= $tituloColuna ?></div>
                            <div class="card-body" style="min-height: 300px;">
                                <?php foreach ($tarefas as $tarefa): ?>
                                    <?php if ($tarefa['status'] !== $statusColuna) continue; ?>
                                    <?php
                                        $cardClass = $tarefa['atrasada'] ? 'border-danger' : ($statusColuna === 'em_andamento' ? 'border-warning' : '');
                                        $badgeHtml = '';

                                        if ($tarefa['atrasada']) {
                                            $badgeHtml = '<span class="badge bg-danger">Atrasada</span>';
                                        } elseif ($statusColuna === 'em_andamento') {
                                            $badgeText = !empty($tarefa['data_limite']) ? 'Dentro do prazo' : 'Em andamento';
                                            $badgeHtml = '<span class="badge bg-warning text-dark">' . $badgeText . '</span>';
                                        }
                                    ?>
                                    <div class="card mb-2 <?= $cardClass ?>">
                                        <div class="card-body p-2">
                                            <strong><?= htmlspecialchars($tarefa['titulo']) ?></strong>
                                            <?= $badgeHtml ?>
                                            <?php if (!empty($tarefa['responsavel_nome'])): ?>
                                                <div class="small text-muted"><?= htmlspecialchars($tarefa['responsavel_nome']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($tarefa['data_limite'])): ?>
                                                <div class="small text-muted">Prazo: <?= htmlspecialchars($tarefa['data_limite']) ?></div>
                                            <?php endif; ?>

                                            <div class="mt-2 d-flex gap-1">
                                                <?php if ($statusColuna === 'a_fazer'): ?>
                                                    <?= botaoMover($tarefa['id'], 'em_andamento', 'Iniciar') ?>
                                                <?php elseif ($statusColuna === 'em_andamento'): ?>
                                                    <?= botaoMover($tarefa['id'], 'a_fazer', 'Voltar') ?>
                                                    <?= botaoMover($tarefa['id'], 'concluido', 'Concluir') ?>
                                                <?php elseif ($statusColuna === 'concluido'): ?>
                                                    <?= botaoMover($tarefa['id'], 'em_andamento', 'Reabrir') ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Chat do projeto -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">Conversa do projeto</div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    <?php if (empty($mensagens)): ?>
                        <p class="text-muted">Nenhuma mensagem ainda.</p>
                    <?php else: ?>
                        <?php foreach ($mensagens as $m): ?>
                            <div class="mb-2">
                                <strong><?= htmlspecialchars($m['autor_nome']) ?>:</strong>
                                <?= nl2br(htmlspecialchars($m['mensagem'])) ?>
                                <span class="small text-muted"><?= htmlspecialchars($m['enviado_em']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <form action="/Projeto/mensagem/<?= (int) $projeto['id'] ?>" method="POST" class="d-flex gap-2">
                        <?= \App\Library\Csrf::getHiddenField() ?>
                        <input type="text" name="mensagem" class="form-control" placeholder="Escreva uma mensagem..." required>
                        <button type="submit" class="btn btn-primary">Enviar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
