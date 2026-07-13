<?php include __DIR__ . '/comuns/header.php'; ?>

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

    <div class="btn-group mb-4" role="group">
        <a href="/Agenda?filtro=hoje" class="btn btn-outline-secondary <?= $filtro === 'hoje' ? 'active' : '' ?>">Hoje</a>
        <a href="/Agenda?filtro=semana" class="btn btn-outline-secondary <?= $filtro === 'semana' ? 'active' : '' ?>">Proximos 7 dias</a>
        <a href="/Agenda?filtro=todos" class="btn btn-outline-secondary <?= $filtro === 'todos' ? 'active' : '' ?>">Todos</a>
    </div>

    <?php if (empty($compromissos)): ?>
        <div class="alert alert-secondary">Nenhum compromisso encontrado para esse filtro.</div>
    <?php else: ?>
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
        <?php foreach ($compromissos as $c): ?>
            <?php
                $atrasado = $c['status'] === 'pendente' && strtotime($c['data_fim']) < time();
            ?>
            <div class="card mb-2 <?= $atrasado ? 'border-danger' : '' ?>">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <strong><?= htmlspecialchars($c['titulo']) ?></strong>
                            <?php if ($atrasado): ?>
                                <span class="badge bg-danger">Atrasado</span>
                            <?php endif; ?>
                            <div class="small text-muted"><?= htmlspecialchars($rotuloTipo[$c['tipo']] ?? $c['tipo']) ?></div>
                        </div>
                        <div class="col-md-3">
                            <div><?= htmlspecialchars(date('d/m/Y H:i', strtotime($c['data_inicio']))) ?></div>
                            <div class="small text-muted">ate <?= htmlspecialchars(date('d/m/Y H:i', strtotime($c['data_fim']))) ?></div>
                        </div>
                        <div class="col-md-2">
                            <span class="badge <?= $corStatus[$c['status']] ?? 'bg-secondary' ?>"><?= htmlspecialchars(ucfirst($c['status'])) ?></span>
                        </div>
                        <div class="col-md-3 text-end">
                            <?php if ($c['status'] === 'pendente'): ?>
                                <a href="/Agenda/form/<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                                <form action="/Agenda/concluir/<?= (int) $c['id'] ?>" method="POST" class="d-inline">
                                    <?= \App\Library\Csrf::getHiddenField() ?>
                                    <button type="submit" class="btn btn-sm btn-success">Concluir</button>
                                </form>
                                <form action="/Agenda/cancelar/<?= (int) $c['id'] ?>" method="POST" class="d-inline">
                                    <?= \App\Library\Csrf::getHiddenField() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Cancelar este compromisso?')">Cancelar</button>
                                </form>
                            <?php else: ?>
                                <form action="/Agenda/excluir/<?= (int) $c['id'] ?>" method="POST" class="d-inline">
                                    <?= \App\Library\Csrf::getHiddenField() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Excluir este compromisso definitivamente?')">Excluir</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
