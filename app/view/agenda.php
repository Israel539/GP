<?php include __DIR__ . '/comuns/header.php';
$filtro = $filtro ?? 'todos'; ?>

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
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-0">
                        <div style="max-height: 320px; overflow-y: auto;" class="p-3">
                            <ul class="list-group list-group-flush mb-0">
                                <?php foreach ($compromissos as $c): ?>
                                    <?php $atrasado = $c['status'] === 'pendente' && strtotime($c['data_fim']) < time(); ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center <?= $atrasado ? 'border-danger' : '' ?>">
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
                                        <div class="text-end">
                                            <div class="mb-1">
                                                <span class="badge <?= $corStatus[$c['status']] ?? 'bg-secondary' ?>"><?= htmlspecialchars(ucfirst($c['status'])) ?></span>
                                            </div>
                                            <?php if ($c['status'] === 'pendente'): ?>
                                                <a href="/Agenda/form/<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-secondary mb-1">Editar</a>
                                                <form action="/Agenda/concluir/<?= (int) $c['id'] ?>" method="POST" class="d-inline">
                                                    <?= \App\Library\Csrf::getHiddenField() ?>
                                                    <button type="submit" class="btn btn-sm btn-success">Concluir</button>
                                                </form>
                                                <form action="/Agenda/cancelar/<?= (int) $c['id'] ?>" method="POST" class="d-inline">
                                                    <?= \App\Library\Csrf::getHiddenField() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancelar este compromisso?')">Cancelar</button>
                                                </form>
                                            <?php else: ?>
                                                <form action="/Agenda/excluir/<?= (int) $c['id'] ?>" method="POST" class="d-inline">
                                                    <?= \App\Library\Csrf::getHiddenField() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir este compromisso definitivamente?')">Excluir</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <h3 class="h5 mb-3">Compromissos recorrentes</h3>
            <?php if (empty($compromissosRecorrentes)): ?>
                <div class="alert alert-secondary">Nenhum compromisso recorrente encontrado para esse filtro.</div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div style="max-height: 320px; overflow-y: auto;" class="p-3">
                            <ul class="list-group list-group-flush mb-0">
                                <?php foreach ($compromissosRecorrentes as $c): ?>
                                    <?php $atrasado = $c['status'] === 'pendente' && strtotime($c['data_fim']) < time(); ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center <?= $atrasado ? 'border-danger' : '' ?>">
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
                                        <div class="text-end">
                                            <div class="mb-1">
                                                <span class="badge <?= $corStatus[$c['status']] ?? 'bg-secondary' ?>"><?= htmlspecialchars(ucfirst($c['status'])) ?></span>
                                            </div>
                                            <?php if ($c['status'] === 'pendente'): ?>
                                                <a href="/Agenda/form/<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-secondary mb-1">Editar</a>
                                                <form action="/Agenda/concluir/<?= (int) $c['id'] ?>" method="POST" class="d-inline">
                                                    <?= \App\Library\Csrf::getHiddenField() ?>
                                                    <button type="submit" class="btn btn-sm btn-success">Concluir</button>
                                                </form>
                                                <form action="/Agenda/cancelar/<?= (int) $c['id'] ?>" method="POST" class="d-inline">
                                                    <?= \App\Library\Csrf::getHiddenField() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancelar este compromisso?')">Cancelar</button>
                                                </form>
                                            <?php else: ?>
                                                <form action="/Agenda/excluir/<?= (int) $c['id'] ?>" method="POST" class="d-inline">
                                                    <?= \App\Library\Csrf::getHiddenField() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir este compromisso definitivamente?')">Excluir</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
