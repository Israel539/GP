<?php
/**
 * @var int $ano
 * @var int $mes
 * @var \DateTime $mesAnterior
 * @var \DateTime $mesSeguinte
 * @var \DateTime $primeiroDia
 * @var array<string, array> $porDia
 * @var string $hojeChave
 */
include __DIR__ . '/comuns/header.php'; ?>

<style>
    .cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 1px;
        background: #dee2e6;
        border: 1px solid #dee2e6;
    }
    .cal-cabecalho {
        background: #f8f9fa;
        text-align: center;
        font-weight: 600;
        font-size: 0.8rem;
        padding: 6px 4px;
        text-transform: uppercase;
        color: #6c757d;
    }
    .cal-dia {
        background: #fff;
        min-height: 110px;
        padding: 4px;
        position: relative;
        display: flex;
        flex-direction: column;
    }
    .cal-dia.fora-do-mes {
        background: #f8f9fa;
    }
    .cal-dia.fora-do-mes .cal-numero {
        color: #adb5bd;
    }
    .cal-dia.hoje {
        background: #fff8e1;
    }
    .cal-numero-linha {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2px;
    }
    .cal-numero {
        font-size: 0.85rem;
        font-weight: 600;
    }
    .cal-dia.hoje .cal-numero {
        background: #0d6efd;
        color: #fff;
        border-radius: 50%;
        width: 22px;
        height: 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .cal-add {
        opacity: 0;
        text-decoration: none;
        font-size: 0.9rem;
        line-height: 1;
    }
    .cal-dia:hover .cal-add {
        opacity: 1;
    }
    .cal-evento {
        display: block;
        font-size: 0.72rem;
        padding: 1px 4px;
        border-radius: 4px;
        margin-bottom: 2px;
        text-decoration: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .cal-evento.tipo-reuniao_presencial { background: #cfe2ff; color: #084298; }
    .cal-evento.tipo-tarefa_pessoal     { background: #d1e7dd; color: #0f5132; }
    .cal-evento.tipo-lembrete           { background: #fff3cd; color: #664d03; }
    .cal-evento.tipo-outro              { background: #e2e3e5; color: #41464b; }
    .cal-evento.status-concluido        { text-decoration: line-through; opacity: 0.65; }
    .cal-evento.status-cancelado        { text-decoration: line-through; opacity: 0.5; }
    .cal-dia.feriado {
        background: #fff0f0;
    }
    .cal-feriado {
        display: block;
        font-size: 0.68rem;
        font-weight: 600;
        color: #b02a37;
        background: #f8d7da;
        border-radius: 4px;
        padding: 1px 4px;
        margin-bottom: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .cal-mais {
        font-size: 0.72rem;
        color: #6c757d;
        text-decoration: none;
    }
    @media (max-width: 767px) {
        .cal-dia { min-height: 70px; }
        .cal-evento { font-size: 0.65rem; }
    }
</style>

<div class="container-fluid py-5" style="max-width: 1100px;">

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
            <a href="/Agenda" class="btn btn-outline-primary"><i class="bi bi-list-ul"></i> Lista</a>
            <a href="/Agenda/calendario" class="btn btn-outline-primary active"><i class="bi bi-calendar3"></i> Calendario</a>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="/Agenda/calendario?ano=<?= $mesAnterior->format('Y') ?>&mes=<?= $mesAnterior->format('n') ?>"
                class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i></a>
            <span class="fw-semibold" style="min-width: 160px; text-align: center;">
                <?php
                    $nomesMes = [1=>'Janeiro',2=>'Fevereiro',3=>'Marco',4=>'Abril',5=>'Maio',6=>'Junho',
                                 7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'];
                ?>
                <?= $nomesMes[$mes] ?> de <?= $ano ?>
            </span>
            <a href="/Agenda/calendario?ano=<?= $mesSeguinte->format('Y') ?>&mes=<?= $mesSeguinte->format('n') ?>"
                class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-right"></i></a>
            <a href="/Agenda/calendario" class="btn btn-outline-secondary btn-sm">Hoje</a>
        </div>
    </div>

    <?php
        // Domingo como primeiro dia da semana (padrao BR).
        $primeiroDiaSemana = (int) $primeiroDia->format('w'); // 0 (dom) a 6 (sab)
        $totalDiasMes      = (int) $primeiroDia->format('t');

        $cursor = (clone $primeiroDia)->modify('-' . $primeiroDiaSemana . ' days');

        $totalCelulas = $primeiroDiaSemana + $totalDiasMes;
        $totalSemanas = (int) ceil($totalCelulas / 7);

        $rotuloTipo = [
            'reuniao_presencial' => 'Reuniao presencial',
            'tarefa_pessoal'     => 'Tarefa pessoal',
            'lembrete'           => 'Lembrete',
            'outro'              => 'Outro',
        ];
    ?>

    <div class="cal-grid">
        <?php foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'] as $diaSemana): ?>
            <div class="cal-cabecalho"><?= $diaSemana ?></div>
        <?php endforeach; ?>

        <?php for ($semana = 0; $semana < $totalSemanas; $semana++): ?>
            <?php for ($diaSemana = 0; $diaSemana < 7; $diaSemana++): ?>
                <?php
                    $chaveDia   = $cursor->format('Y-m-d');
                    $foraDoMes  = (int) $cursor->format('n') !== $mes;
                    $ehHoje     = $chaveDia === $hojeChave;
                    $nomeFeriado = $feriados[$chaveDia] ?? null;
                    $eventosDia = $porDia[$chaveDia] ?? [];
                    $limite     = 3;
                ?>
                <div class="cal-dia <?= $foraDoMes ? 'fora-do-mes' : '' ?> <?= $ehHoje ? 'hoje' : '' ?> <?= $nomeFeriado ? 'feriado' : '' ?>">
                    <div class="cal-numero-linha">
                        <span class="cal-numero"><?= (int) $cursor->format('j') ?></span>
                        <a class="cal-add" href="/Agenda/form?data=<?= $chaveDia ?>" title="Novo compromisso neste dia">
                            <i class="bi bi-plus-lg"></i>
                        </a>
                    </div>

                    <?php if ($nomeFeriado): ?>
                        <span class="cal-feriado" title="Feriado nacional: <?= htmlspecialchars($nomeFeriado) ?>">
                            <i class="bi bi-flag-fill"></i> <?= htmlspecialchars($nomeFeriado) ?>
                        </span>
                    <?php endif; ?>

                    <?php foreach (array_slice($eventosDia, 0, $limite) as $evento): ?>
                        <a class="cal-evento tipo-<?= htmlspecialchars($evento['tipo']) ?> status-<?= htmlspecialchars($evento['status']) ?>"
                            href="/Agenda/form/<?= (int) $evento['id'] ?>"
                            title="<?= htmlspecialchars($rotuloTipo[$evento['tipo']] ?? $evento['tipo']) ?> - <?= htmlspecialchars(date('H:i', strtotime($evento['data_inicio']))) ?>">
                            <?= htmlspecialchars(date('H:i', strtotime($evento['data_inicio']))) ?>
                            <?= htmlspecialchars($evento['titulo']) ?>
                        </a>
                    <?php endforeach; ?>

                    <?php if (count($eventosDia) > $limite): ?>
                        <a class="cal-mais" href="/Agenda?filtro=todos"><?= count($eventosDia) - $limite ?> mais...</a>
                    <?php endif; ?>
                </div>
                <?php $cursor->modify('+1 day'); ?>
            <?php endfor; ?>
        <?php endfor; ?>
    </div>

    <div class="d-flex gap-3 mt-3 small text-muted flex-wrap">
        <span><span class="badge" style="background:#f8d7da;color:#b02a37;">&nbsp;&nbsp;</span> Feriado nacional</span>
        <span><span class="badge" style="background:#cfe2ff;color:#084298;">&nbsp;&nbsp;</span> Reuniao presencial</span>
        <span><span class="badge" style="background:#d1e7dd;color:#0f5132;">&nbsp;&nbsp;</span> Tarefa pessoal</span>
        <span><span class="badge" style="background:#fff3cd;color:#664d03;">&nbsp;&nbsp;</span> Lembrete</span>
        <span><span class="badge" style="background:#e2e3e5;color:#41464b;">&nbsp;&nbsp;</span> Outro</span>
    </div>

</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
