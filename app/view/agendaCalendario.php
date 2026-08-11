<?php
/**
 * @var int $ano
 * @var int $mes
 * @var \DateTime $primeiroDia
 * @var \DateTime $mesAnterior
 * @var \DateTime $mesSeguinte
 * @var array<string,array> $porDia
 * @var array<string,string> $feriados
 * @var array<string,string> $comemorativas
 * @var string $hojeChave
 */
include __DIR__ . '/comuns/header.php'; ?>

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

        <a href="/CompromissoRecorrente" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-repeat"></i> Atividades recorrentes
        </a>

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
                    $nomeComemorativa = $comemorativas[$chaveDia] ?? null;
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

                    <?php if ($nomeComemorativa): ?>
                        <span class="cal-comemorativa" title="Data comemorativa: <?= htmlspecialchars($nomeComemorativa) ?>">
                            <i class="bi bi-heart-fill"></i> <?= htmlspecialchars($nomeComemorativa) ?>
                        </span>
                    <?php endif; ?>

                    <?php foreach (array_slice($eventosDia, 0, $limite) as $evento): ?>
                        <?php $ehRecorrente = !empty($evento['recorrencia_id']); ?>
                        <a class="cal-evento tipo-<?= htmlspecialchars($evento['tipo']) ?> status-<?= htmlspecialchars($evento['status']) ?> <?= $ehRecorrente ? 'cal-evento-recorrente' : '' ?>"
                            href="/Agenda/form/<?= (int) $evento['id'] ?>"
                            title="<?= htmlspecialchars($rotuloTipo[$evento['tipo']] ?? $evento['tipo']) ?> - <?= htmlspecialchars(date('H:i', strtotime($evento['data_inicio']))) ?>">
                            <?= htmlspecialchars(date('H:i', strtotime($evento['data_inicio']))) ?>
                            <?php if (!empty($evento['recorrencia_id'])): ?><i class="bi bi-arrow-repeat" title="Atividade recorrente"></i><?php endif; ?>
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
        <span><span class="badge" style="background:#f1d9f5;color:#6f1d75;">&nbsp;&nbsp;</span> Data comemorativa</span>
        <span><span class="badge" style="background:#cfe2ff;color:#084298;">&nbsp;&nbsp;</span> Reuniao presencial</span>
        <span><span class="badge" style="background:#d1e7dd;color:#0f5132;">&nbsp;&nbsp;</span> Tarefa pessoal</span>
        <span><span class="badge" style="background:#fff3cd;color:#664d03;">&nbsp;&nbsp;</span> Lembrete</span>
        <span><span class="badge" style="background:#e2e3e5;color:#41464b;">&nbsp;&nbsp;</span> Outro</span>
        <span><span class="badge cal-evento-recorrente" style="background:#cfe2ff;color:#084298;">&nbsp;&nbsp;</span> Compromisso recorrente (borda roxa)</span>
    </div>

</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
