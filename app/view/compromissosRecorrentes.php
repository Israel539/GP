<?php
/** @var array $recorrencias */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <a href="/Agenda" class="btn btn-outline-secondary btn-sm mb-3">
        <i class="bi bi-arrow-left"></i> Voltar pra Agenda
    </a>

    <div class="row mb-4">
        <div class="col-8">
            <h2>Atividades Recorrentes</h2>
            <p class="text-muted small mb-0">Ex: aula toda segunda-feira. Vira compromisso de verdade na agenda automaticamente.</p>
        </div>
        <div class="col-4 text-end">
            <form action="/CompromissoRecorrente/gerarAgora" method="POST" class="d-inline">
                <?= \App\Library\Csrf::getHiddenField() ?>
                <button type="submit" class="btn btn-outline-success">
                    <i class="bi bi-arrow-repeat"></i> Gerar agora
                </button>
            </form>
            <a href="/CompromissoRecorrente/form" class="btn btn-primary">+ Nova atividade recorrente</a>
        </div>
    </div>

    <p class="text-muted small">
        As proximas 8 semanas de cada atividade sao mantidas geradas automaticamente
        (via cron, ou clicando em "Gerar agora"). Editar aqui so vale pras proximas
        ocorrencias -- os compromissos ja criados na agenda continuam editaveis
        normalmente, um por um.
    </p>

    <?= mensagens() ?>

    <?php if (empty($recorrencias)): ?>
        <div class="alert alert-secondary">Nenhuma atividade recorrente cadastrada ainda.</div>
    <?php else: ?>
        <?php
            $nomesDiaSemana = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
        ?>
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Quando</th>
                    <th>Válido</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recorrencias as $r): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($r['titulo']) ?>
                            <?php if (!empty($r['local'])): ?>
                                <div class="small text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($r['local']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            Toda <?= $nomesDiaSemana[(int) $r['dia_semana']] ?><br>
                            <span class="small text-muted"><?= substr($r['hora_inicio'], 0, 5) ?> às <?= substr($r['hora_fim'], 0, 5) ?></span>
                        </td>
                        <td class="small text-muted">
                            A partir de <?= htmlspecialchars(date('d/m/Y', strtotime($r['data_inicio']))) ?>
                            <?php if (!empty($r['data_fim'])): ?>
                                <br>até <?= htmlspecialchars(date('d/m/Y', strtotime($r['data_fim']))) ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="/CompromissoRecorrente/form/<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                            <form action="/CompromissoRecorrente/excluir/<?= (int) $r['id'] ?>" method="POST" class="d-inline">
                                <?= \App\Library\Csrf::getHiddenField() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Excluir esta atividade recorrente? Isso apagará também os compromissos gerados por ela na agenda.')">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
