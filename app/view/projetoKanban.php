<?php
/** @var array $projeto */
/** @var array $colaboradores */
/** @var array $tarefas */
/** @var array $usuario */
/** @var bool $usuarioEhDono */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container-fluid py-4">

    <a href="/Projeto" class="btn btn-outline-secondary btn-sm mb-3">
        <i class="bi bi-arrow-left"></i> Voltar para projetos
    </a>

    <div class="row mb-3">
        <div class="col-8">
            <h2><?= htmlspecialchars($projeto['nome']) ?></h2>
            <span class="badge bg-info text-dark"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $projeto['status']))) ?></span>
        </div>
        <div class="col-4 text-end">
            <?php if ($projeto['status'] !== 'concluido' && isset($usuario) && $usuario['id'] === (int) $projeto['dono_id']): ?>
                <form action="/Projeto/concluir/<?= (int) $projeto['id'] ?>" method="POST" class="d-inline">
                    <?= \App\Library\Csrf::getHiddenField() ?>
                    <button type="submit" class="btn btn-success btn-sm"
                        onclick="return confirm('Concluir este projeto? So funciona se nao houver tarefa pendente.')">
                        Concluir projeto
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($projeto['status'] === 'concluido' && isset($usuario) && $usuario['id'] === (int) $projeto['dono_id']): ?>
                <form action="/Projeto/excluir/<?= (int) $projeto['id'] ?>" method="POST" class="d-inline ms-2">
                    <?= \App\Library\Csrf::getHiddenField() ?>
                    <button type="submit" class="btn btn-outline-danger btn-sm"
                        onclick="return confirm('Excluir este projeto concluído? Esta ação não pode ser desfeita.')">
                        Apagar projeto
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
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <?= htmlspecialchars($c['nome']) ?>
                                <span class="badge bg-secondary ms-2"><?= htmlspecialchars($c['papel']) ?></span>
                            </div>
                            <?php if (isset($usuario) && $usuarioEhDono && $c['papel'] === 'colaborador'): ?>
                                <form action="/Projeto/removerColaborador/<?= (int) $projeto['id'] ?>/<?= (int) $c['id'] ?>" method="POST" class="m-0">
                                    <?= \App\Library\Csrf::getHiddenField() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                </form>
                            <?php endif; ?>
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

                    <?php if (isset($usuario) && !$usuarioEhDono): ?>
                        <form action="/Projeto/sair/<?= (int) $projeto['id'] ?>" method="POST" class="mt-3">
                            <?= \App\Library\Csrf::getHiddenField() ?>
                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100" onclick="return confirm('Sair deste projeto?');">Sair do projeto</button>
                        </form>
                    <?php endif; ?>
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

                <!-- Anotacoes fica escondida por padrao pra nao poluir o
                     formulario -- so aparece se a pessoa quiser usar. -->
                <div class="mt-2">
                    <a class="small" data-bs-toggle="collapse" href="#anotacoesNovaTarefa" role="button">
                        <i class="bi bi-plus-circle"></i> Adicionar anotacoes
                    </a>
                    <div class="collapse mt-1" id="anotacoesNovaTarefa">
                        <textarea name="descricao" class="form-control form-control-sm" rows="3"
                            placeholder="Detalhes, links, o que for preciso anotar sobre essa tarefa..."></textarea>
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
                                        if ($tarefa['atrasada']) {
                                            $cardClass = 'border border-danger';
                                            $badgeHtml = '<span class="badge bg-danger">Atrasada</span>';
                                        } elseif ($statusColuna === 'a_fazer' && !empty($tarefa['prazo_valido'])) {
                                            $cardClass = 'border border-warning';
                                            $badgeHtml = '<span class="badge bg-warning text-dark">Dentro do prazo</span>';
                                        } elseif ($statusColuna === 'em_andamento') {
                                            $cardClass = 'border border-warning';
                                            $badgeText = !empty($tarefa['prazo_valido']) ? 'Dentro do prazo' : 'Em andamento';
                                            $badgeHtml = '<span class="badge bg-warning text-dark">' . $badgeText . '</span>';
                                        } elseif ($statusColuna === 'concluido') {
                                            $cardClass = 'border border-success';
                                            $badgeHtml = '<span class="badge bg-success">Concluida</span>';
                                        } else {
                                            $cardClass = '';
                                            $badgeHtml = '';
                                        }
                                    ?>
                                    <div class="card mb-2 <?= $cardClass ?>">
                                        <div class="card-body p-2">
                                            <strong><?= htmlspecialchars($tarefa['titulo']) ?></strong>
                                            <?= $badgeHtml ?>
                                            <?php if (!empty($tarefa['descricao'])): ?>
                                                <i class="bi bi-card-text text-muted small" title="Tem anotacoes"></i>
                                            <?php endif; ?>
                                            <?php if (!empty($tarefa['responsavel_nome'])): ?>
                                                <div class="small text-muted"><?= htmlspecialchars($tarefa['responsavel_nome']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($tarefa['prazo_valido'])): ?>
                                                <div class="small text-muted">Prazo: <?= htmlspecialchars($tarefa['data_limite']) ?></div>
                                            <?php endif; ?>

                                            <div class="mt-2 d-flex gap-1 flex-wrap">
                                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#tarefaModal<?= (int) $tarefa['id'] ?>">
                                                    <i class="bi bi-eye"></i> Ver
                                                </button>

                                                <?php if ($statusColuna === 'a_fazer'): ?>
                                                    <?= botaoMover($tarefa['id'], 'em_andamento', 'Iniciar') ?>
                                                <?php elseif ($statusColuna === 'em_andamento'): ?>
                                                    <?= botaoMover($tarefa['id'], 'a_fazer', 'Voltar') ?>
                                                    <?= botaoMover($tarefa['id'], 'concluido', 'Concluir') ?>
                                                <?php elseif ($statusColuna === 'concluido'): ?>
                                                    <?= botaoMover($tarefa['id'], 'em_andamento', 'Reabrir') ?>
                                                <?php endif; ?>

                                                <?php if ((int) $tarefa['responsavel_id'] === (int) $usuario['id']): ?>
                                                    <form action="/Tarefa/excluir/<?= (int) $tarefa['id'] ?>" method="POST" class="d-inline">
                                                        <?= \App\Library\Csrf::getHiddenField() ?>
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Excluir esta tarefa?');">Excluir</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal de ver/editar tarefa -- anotacoes so aparecem aqui dentro,
                                         nao no card, pra nao poluir o quadro visualmente. -->
                                    <div class="modal fade" id="tarefaModal<?= (int) $tarefa['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="/Tarefa/atualizar/<?= (int) $tarefa['id'] ?>" method="POST">
                                                    <?= \App\Library\Csrf::getHiddenField() ?>
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Detalhes da tarefa</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-2">
                                                            <label class="form-label small">Titulo</label>
                                                            <input type="text" name="titulo" class="form-control form-control-sm"
                                                                value="<?= htmlspecialchars($tarefa['titulo']) ?>" required minlength="3" maxlength="150">
                                                        </div>
                                                        <div class="row g-2 mb-2">
                                                            <div class="col-md-6">
                                                                <label class="form-label small">Responsavel</label>
                                                                <select name="responsavel_id" class="form-select form-select-sm">
                                                                    <option value="">-- ninguem --</option>
                                                                    <?php foreach ($colaboradores as $c): ?>
                                                                        <option value="<?= (int) $c['id'] ?>"
                                                                            <?= (int) $tarefa['responsavel_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($c['nome']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label small">Prazo</label>
                                                                <input type="date" name="data_limite" class="form-control form-control-sm"
                                                                    value="<?= !empty($tarefa['prazo_valido']) ? htmlspecialchars($tarefa['data_limite']) : '' ?>">
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <label class="form-label small">Anotacoes</label>
                                                            <textarea name="descricao" class="form-control form-control-sm" rows="6"
                                                                placeholder="Detalhes, links, o que for preciso anotar sobre essa tarefa..."><?= htmlspecialchars($tarefa['descricao'] ?? '') ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
                                                        <button type="submit" class="btn btn-primary btn-sm">Salvar</button>
                                                    </div>
                                                </form>
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
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Conversa do projeto</span>
                  
                </div>
                <div class="card-body p-0">
                    <div style="max-height: 320px; overflow-y: auto;" class="p-3">
                        <?php if (empty($mensagens)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-chat-left-text fs-3 d-block mb-2"></i>
                                <p class="mb-0">Nenhuma mensagem ainda.</p>
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($mensagens as $m): ?>
                                    <div class="border rounded p-2 bg-light">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <strong><?= htmlspecialchars($m['autor_nome']) ?></strong>
                                            <small class="text-muted"><?= htmlspecialchars($m['enviado_em']) ?></small>
                                        </div>
                                        <div class="mt-1 small">
                                            <?= nl2br(htmlspecialchars($m['mensagem'])) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
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
