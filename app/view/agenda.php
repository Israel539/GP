<?php include __DIR__ . '/comuns/header.php';
$filtro     = $filtro ?? 'todos';
$busca      = $busca ?? '';
$dataBusca  = $dataBusca ?? '';
$limpezaAutomaticaAtiva = $limpezaAutomaticaAtiva ?? false;
?>

<div class="container py-5">

    <div class="row mb-4">
        <div class="col-8">
            <h2>Minha Agenda</h2>
        </div>
        <div class="col-4 text-end">
            <a href="/Agenda/form" class="btn btn-primary">+ Novo compromisso</a>
        </div>
    </div>

    <?= mensagens() ?>

    <!-- Filtro de busca (item 6): por titulo e/ou por data especifica. GET
         simples, no mesmo espirito dos botoes hoje/semana/todos abaixo --
         o filtro atual continua marcado ao lado. -->
    <div class="card card-custom mb-4">
        <div class="card-body p-3">
            <form id="formFiltroAgenda" class="row g-2 align-items-end" action="/Agenda" method="GET">
                <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
                <div class="col-md-6">
                    <label class="form-label small text-muted mb-1">Buscar por titulo</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" id="buscaAgenda" name="busca"
                            class="form-control border-start-0"
                            placeholder="Ex: reuniao, dentista..."
                            value="<?= htmlspecialchars($busca) ?>"
                            autocomplete="off">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Buscar por data</label>
                    <input type="date" id="dataBuscaAgenda" name="dataBusca" class="form-control"
                        value="<?= htmlspecialchars($dataBusca) ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">Filtrar</button>
                    <?php if ($busca !== '' || $dataBusca !== ''): ?>
                        <a href="/Agenda?filtro=<?= htmlspecialchars($filtro) ?>" class="btn btn-outline-secondary" title="Limpar filtro">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div class="btn-group" role="group">
            <a href="/Agenda?filtro=hoje" class="btn btn-outline-secondary <?= $filtro === 'hoje' ? 'active' : '' ?>">Hoje</a>
            <a href="/Agenda?filtro=semana" class="btn btn-outline-secondary <?= $filtro === 'semana' ? 'active' : '' ?>">Proximos 7 dias</a>
            <a href="/Agenda?filtro=todos" class="btn btn-outline-secondary <?= $filtro === 'todos' ? 'active' : '' ?>">Todos</a>
        </div>
        <div class="btn-group" role="group">
            <a href="/Agenda" class="btn btn-outline-primary active"><i class="bi bi-list-ul"></i> Lista</a>
            <a href="/Agenda/calendario" class="btn btn-outline-primary"><i class="bi bi-calendar3"></i> Calendario</a>
        </div>
        <a href="/CompromissoRecorrente" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-repeat"></i> Atividades recorrentes
        </a>
    </div>


    <div class="card card-custom mb-4">
        <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <strong>Exclusao automatica de concluidos</strong>
                <div class="small text-muted">
                    Quando ativado, compromissos com status "Concluido" ha mais de 30 dias sao apagados
                    automaticamente pela rotina agendada do sistema.
                </div>
            </div>
            <form action="/Agenda/configurarLimpezaAutomatica" method="POST" class="d-flex align-items-center gap-2">
                <?= \App\Library\Csrf::getHiddenField() ?>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="agenda_limpeza_automatica"
                        name="agenda_limpeza_automatica" value="1" onchange="this.form.submit()"
                        <?= $limpezaAutomaticaAtiva ? 'checked' : '' ?>>
                    <label class="form-check-label" for="agenda_limpeza_automatica">
                        <?= $limpezaAutomaticaAtiva ? 'Ativada' : 'Desativada' ?>
                    </label>
                </div>
            </form>
        </div>
    </div>

    <?php
        $rotuloTipo = [
            'reuniao_presencial' => 'Reuniao presencial',
            'tarefa_pessoal'     => 'Tarefa pessoal',
            'lembrete'           => 'Lembrete',
            'outro'              => 'Outro',
        ];
        $corStatus = [
            'pendente'  => 'bg-info text-dark',
            'concluido' => 'bg-success',
            'cancelado' => 'bg-secondary',
        ];
    ?>

    <?php if (empty($compromissos) && empty($compromissosRecorrentes)): ?>
        <div class="alert alert-secondary">Nenhum compromisso encontrado para esse filtro.</div>
    <?php else: ?>
        <div class="mb-4">
            <h3 class="h5 mb-3">Compromissos</h3>
            <?php if (empty($compromissos)): ?>
                <div class="alert alert-secondary">Nenhum compromisso normal encontrado para esse filtro.</div>
            <?php else: ?>
                <form action="/Agenda/excluirEmMassa" method="POST" class="form-selecao-massa" data-lista="normal">
                    <?= \App\Library\Csrf::getHiddenField() ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                            <div class="form-check mb-0">
                                <input class="form-check-input chk-selecionar-todos" type="checkbox"
                                    id="selTodosNormal" data-alvo="normal">
                                <label class="form-check-label small" for="selTodosNormal">Selecionar todos</label>
                            </div>
                            <button type="submit" class="btn btn-sm btn-outline-danger btn-excluir-selecionados" disabled
                                onclick="return confirm('Excluir os compromissos selecionados? Essa acao nao pode ser desfeita.')">
                                <i class="bi bi-trash"></i> Excluir selecionados
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div style="max-height: 320px; overflow-y: auto;" class="p-3">
                                <ul class="list-group list-group-flush mb-0">
                                    <?php foreach ($compromissos as $c): ?>
                                        <?php $atrasado = $c['status'] === 'pendente' && strtotime($c['data_fim']) < time(); ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center <?= $atrasado ? 'border-danger' : '' ?>">
                                            <div class="d-flex align-items-start gap-2">
                                                <input class="form-check-input mt-1 chk-item chk-normal" type="checkbox"
                                                    name="ids[]" value="<?= (int) $c['id'] ?>">
                                                <div>
                                                    <strong><?= htmlspecialchars($c['titulo']) ?></strong>
                                                    <?php if ($atrasado): ?>
                                                        <span class="badge bg-danger">Atrasado</span>
                                                    <?php endif; ?>
                                                    <div class="small text-muted"><?= htmlspecialchars($rotuloTipo[$c['tipo']] ?? $c['tipo']) ?></div>
                                                    <div class="small text-muted">
                                                        <?= htmlspecialchars(date('d/m/Y H:i', strtotime($c['data_inicio']))) ?>
                                                        até <?= htmlspecialchars(date('d/m/Y H:i', strtotime($c['data_fim']))) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="mb-1">
                                                    <span class="badge <?= $corStatus[$c['status']] ?? 'bg-secondary' ?>"><?= htmlspecialchars(ucfirst($c['status'])) ?></span>
                                                </div>
                                                <?php if ($c['status'] === 'pendente'): ?>
                                                    <a href="/Agenda/form/<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-secondary mb-1">Editar</a>
                                                    <button type="submit" formaction="/Agenda/concluir/<?= (int) $c['id'] ?>" class="btn btn-sm btn-success">Concluir</button>
                                                    <button type="submit" formaction="/Agenda/cancelar/<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); return confirm('Cancelar este compromisso?')">Cancelar</button>
                                                <?php else: ?>
                                                    <button type="submit" formaction="/Agenda/excluir/<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); return confirm('Excluir este compromisso definitivamente?')">Excluir</button>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <div>
            <h3 class="h5 mb-3">Compromissos recorrentes</h3>
            <?php if (empty($compromissosRecorrentes)): ?>
                <div class="alert alert-secondary">Nenhum compromisso recorrente encontrado para esse filtro.</div>
            <?php else: ?>
                <form action="/Agenda/excluirEmMassa" method="POST" class="form-selecao-massa" data-lista="recorrente">
                    <?= \App\Library\Csrf::getHiddenField() ?>
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                            <div class="form-check mb-0">
                                <input class="form-check-input chk-selecionar-todos" type="checkbox"
                                    id="selTodosRecorrente" data-alvo="recorrente">
                                <label class="form-check-label small" for="selTodosRecorrente">Selecionar todos</label>
                            </div>
                            <button type="submit" class="btn btn-sm btn-outline-danger btn-excluir-selecionados" disabled
                                onclick="return confirm('Excluir as ocorrencias selecionadas? Essa acao nao pode ser desfeita.')">
                                <i class="bi bi-trash"></i> Excluir selecionados
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div style="max-height: 320px; overflow-y: auto;" class="p-3">
                                <ul class="list-group list-group-flush mb-0">
                                    <?php foreach ($compromissosRecorrentes as $c): ?>
                                        <?php $atrasado = $c['status'] === 'pendente' && strtotime($c['data_fim']) < time(); ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center <?= $atrasado ? 'border-danger' : '' ?>">
                                            <div class="d-flex align-items-start gap-2">
                                                <input class="form-check-input mt-1 chk-item chk-recorrente" type="checkbox"
                                                    name="ids[]" value="<?= (int) $c['id'] ?>">
                                                <div>
                                                    <strong><?= htmlspecialchars($c['titulo']) ?></strong>
                                                    <?php if ($atrasado): ?>
                                                        <span class="badge bg-danger">Atrasado</span>
                                                    <?php endif; ?>
                                                    <div class="small text-muted"><?= htmlspecialchars($rotuloTipo[$c['tipo']] ?? $c['tipo']) ?> • Recorrente</div>
                                                    <div class="small text-muted">
                                                        <?= htmlspecialchars(date('d/m/Y H:i', strtotime($c['data_inicio']))) ?>
                                                        até <?= htmlspecialchars(date('d/m/Y H:i', strtotime($c['data_fim']))) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="mb-1">
                                                    <span class="badge <?= $corStatus[$c['status']] ?? 'bg-secondary' ?>"><?= htmlspecialchars(ucfirst($c['status'])) ?></span>
                                                </div>
                                                <?php if ($c['status'] === 'pendente'): ?>
                                                    <a href="/Agenda/form/<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-secondary mb-1">Editar</a>
                                                    <button type="submit" formaction="/Agenda/concluir/<?= (int) $c['id'] ?>" class="btn btn-sm btn-success">Concluir</button>
                                                    <button type="submit" formaction="/Agenda/cancelar/<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); return confirm('Cancelar este compromisso?')">Cancelar</button>
                                                <?php else: ?>
                                                    <button type="submit" formaction="/Agenda/excluir/<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); return confirm('Excluir este compromisso definitivamente?')">Excluir</button>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.form-selecao-massa').forEach(function (form) {
            var chkTodos = form.querySelector('.chk-selecionar-todos');
            var chkItens = form.querySelectorAll('.chk-item');
            var btnExcluir = form.querySelector('.btn-excluir-selecionados');

            function atualizarBotao() {
                var algumMarcado = Array.prototype.some.call(chkItens, function (c) { return c.checked; });
                btnExcluir.disabled = !algumMarcado;
            }

            if (chkTodos) {
                chkTodos.addEventListener('change', function () {
                    chkItens.forEach(function (c) { c.checked = chkTodos.checked; });
                    atualizarBotao();
                });
            }

            chkItens.forEach(function (c) {
                c.addEventListener('change', function () {
                    if (!c.checked && chkTodos) {
                        chkTodos.checked = false;
                    }
                    atualizarBotao();
                });
            });

            atualizarBotao();
        });
    });
</script>

<?php include __DIR__ . '/comuns/footer.php'; ?>
