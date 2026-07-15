<?php
/** @var int $totalProjetos */
/** @var int $totalContas */
/** @var int $totalPlanosCompra */
/** @var float|int $saldoTotal */
include __DIR__ . '/comuns/header.php'; ?>

<?php if (!empty($estaLogado)): ?>

    <!-- Dashboard de quem esta logado -->
    <div class="container py-5">

        <div class="row align-items-center mb-4">
            <div class="col-md-8">
                <div class="p-4 rounded shadow-sm px-4 py-4" style="background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%); color: #fff;">
                    <h2 class="mb-1"><?= htmlspecialchars($saudacao) ?>, <?= htmlspecialchars($nome) ?>!</h2>
                    <p class="mb-0 opacity-75"><?= htmlspecialchars(ucfirst(diaSemana_pt((int) date('N')))) ?>, <?= (int) date('d') ?> de <?= htmlspecialchars(mes_pt((int) date('n'))) ?></p>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="/Projeto" class="btn btn-light btn-lg"><i class="bi bi-arrow-right-circle me-1"></i> Ir para Projetos</a>
            </div>
        </div>

        <?= mensagens() ?>

        <?php if (!empty($totalAtrasados)): ?>
            <div class="alert alert-warning d-flex align-items-center shadow-sm mb-4">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                <div class="flex-grow-1">
                    <strong><?= (int) $totalAtrasados ?></strong>
                    <?= $totalAtrasados === 1 ? 'compromisso atrasado precisa' : 'compromissos atrasados precisam' ?> da sua atenção.
                </div>
                <a href="/Agenda" class="btn btn-sm btn-warning">Ver agenda</a>
            </div>
        <?php endif; ?>

        <!-- Acoes rapidas -->
        <div class="row g-2 mb-4">
            <div class="col-6 col-md-3">
                <a href="/Agenda/form" class="btn btn-outline-info w-100 py-2"><i class="bi bi-calendar-plus me-2"></i>Novo compromisso</a>
            </div>
            <div class="col-6 col-md-3">
                <a href="/Projeto/form" class="btn btn-outline-primary w-100 py-2"><i class="bi bi-kanban me-2"></i>Novo projeto</a>
            </div>
            <div class="col-6 col-md-3">
                <a href="/Conta" class="btn btn-outline-success w-100 py-2"><i class="bi bi-cash-coin me-2"></i>Lançar transação</a>
            </div>
            <div class="col-6 col-md-3">
                <a href="/PlanoCompra/form" class="btn btn-outline-warning w-100 py-2"><i class="bi bi-bag-plus me-2"></i>Novo plano de compra</a>
            </div>
        </div>

        <!-- Cards de resumo, um por modulo, com cor/icone proprio -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm border-start border-4 border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="text-uppercase small text-muted fw-semibold">Projetos</span>
                            <i class="bi bi-kanban fs-4 text-primary"></i>
                        </div>
                        <h1 class="display-6 mb-1"><?= (int) $totalProjetos ?></h1>
                        <a href="/Projeto" class="small">Ver projetos <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm border-start border-4 border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="text-uppercase small text-muted fw-semibold">Saldo</span>
                            <i class="bi bi-cash-stack fs-4 text-success"></i>
                        </div>
                        <h1 class="display-6 mb-1 <?= $saldoTotal < 0 ? 'text-danger' : '' ?>">R$ <?= number_format((float) $saldoTotal, 2, ',', '.') ?></h1>
                        <a href="/Conta" class="small">Ver contas (<?= (int) $totalContas ?>) <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm border-start border-4 border-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="text-uppercase small text-muted fw-semibold">Compras</span>
                            <i class="bi bi-bag-heart fs-4 text-warning"></i>
                        </div>
                        <h1 class="display-6 mb-1"><?= (int) $totalPlanosCompra ?></h1>
                        <a href="/PlanoCompra" class="small">Ver planos <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm border-start border-4 border-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="text-uppercase small text-muted fw-semibold">Agenda</span>
                            <i class="bi bi-calendar-week fs-4 text-info"></i>
                        </div>
                        <h1 class="display-6 mb-1"><?= count($proximosCompromissos) ?></h1>
                        <a href="/Agenda" class="small">Ver agenda <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumo financeiro do mes -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h6 class="text-uppercase small text-muted fw-semibold mb-3">Movimentação de <?= htmlspecialchars(mes_pt((int) date('n'))) ?></h6>
                <?php
                    $receitas = $resumoMes['receitas'] ?? 0.0;
                    $despesas = $resumoMes['despesas'] ?? 0.0;
                    $totalMes = $receitas + $despesas;
                    $pctReceita = $totalMes > 0 ? ($receitas / $totalMes) * 100 : 0;
                    $pctDespesa = $totalMes > 0 ? ($despesas / $totalMes) * 100 : 0;
                ?>
                <?php if ($totalMes == 0): ?>
                    <p class="text-muted mb-0">Nenhuma movimentação financeira registrada este mês.</p>
                <?php else: ?>
                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width: <?= $pctReceita ?>%"></div>
                        <div class="progress-bar bg-danger" style="width: <?= $pctDespesa ?>%"></div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <span class="badge bg-success bg-opacity-25 text-success">●</span>
                            Receitas: <strong class="text-success">R$ <?= number_format($receitas, 2, ',', '.') ?></strong>
                        </div>
                        <div class="col-6 text-end">
                            Despesas: <strong class="text-danger">R$ <?= number_format($despesas, 2, ',', '.') ?></strong>
                            <span class="badge bg-danger bg-opacity-25 text-danger">●</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <!-- Proximos compromissos -->
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center bg-white">
                        <div>
                            <h5 class="mb-0"><i class="bi bi-calendar-week text-info me-1"></i> Próximos compromissos</h5>
                        </div>
                        <a href="/Agenda" class="btn btn-sm btn-outline-info">Ver tudo</a>
                    </div>
                    <?php if (empty($proximosCompromissos)): ?>
                        <div class="card-body">
                            <p class="text-muted mb-0">Nenhum compromisso pendente nos próximos dias.</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($proximosCompromissos as $c): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($c['titulo']) ?></strong>
                                        <div class="small text-muted"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($c['data_inicio']))) ?></div>
                                    </div>
                                    <a href="/Agenda/form/<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-secondary">Ver</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Projetos recentes -->
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center bg-white">
                        <h5 class="mb-0"><i class="bi bi-kanban text-primary me-1"></i> Projetos recentes</h5>
                        <a href="/Projeto" class="btn btn-sm btn-outline-primary">Ver tudo</a>
                    </div>
                    <?php if (empty($projetosRecentes)): ?>
                        <div class="card-body">
                            <p class="text-muted mb-0">Você ainda não participa de nenhum projeto.</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($projetosRecentes as $p): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($p['nome']) ?></strong>
                                        <div class="small text-muted"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $p['status']))) ?></div>
                                    </div>
                                    <a href="/Projeto/kanban/<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-secondary">Abrir</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>

    <!-- Pagina de apresentacao para visitantes -->
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h1 class="display-5 fw-bold">GP</h1>
                <p class="lead text-muted">O sistema que você precisa para colocar sua vida nos eixos.</p>
                <p class="mb-4">Organize seus projetos, controle suas finanças e gerencie sua agenda com mais clareza e simplicidade.</p>
                <a href="/Login" class="btn btn-primary btn-lg me-2">Entrar</a>
                <a href="/Cadastro" class="btn btn-outline-secondary btn-lg">Cadastrar</a>

                <div class="d-flex flex-wrap gap-4 mt-4">
                    <div class="d-flex align-items-center text-muted small">
                        <i class="bi bi-check-circle-fill text-success me-2"></i> Gratuito para uso pessoal
                    </div>
                    <div class="d-flex align-items-center text-muted small">
                        <i class="bi bi-people-fill text-primary me-2"></i> Projetos colaborativos
                    </div>
                    <div class="d-flex align-items-center text-muted small">
                        <i class="bi bi-shield-lock-fill text-secondary me-2"></i> Seus dados, sua conta
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <img src="<?= baseUrl() ?>assets/img/home/agenda_Pe.jpg" class="card-img-top" alt="Agenda pessoal">
                    <div class="card-body">
                        <h5 class="card-title">Comece agora</h5>
                        <p class="card-text text-muted">Acesse o sistema e veja como é fácil se organizar.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5 pt-3">
            <h3 class="fw-bold">Tudo o que você precisa, em um só lugar</h3>
            <p class="text-muted">Quatro pilares pensados pra sua vida pessoal, sem precisar de quatro apps diferentes.</p>
        </div>

        <div class="row mt-3 g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm border-0 position-relative overflow-hidden">
                    <span class="position-absolute top-0 start-0 m-3 d-flex align-items-center justify-content-center rounded-circle bg-info bg-opacity-75 text-white"
                        style="width: 44px; height: 44px; z-index: 2;">
                        <i class="bi bi-calendar-week fs-5"></i>
                    </span>
                    <img src="<?= baseUrl() ?>assets/img/home/agenda_Pe.jpg" class="card-img-top" alt="Agenda Pessoal">
                    <div class="card-body border-top border-info border-3">
                        <h5 class="card-title">Agenda Pessoal</h5>
                        <p class="card-text text-muted small">Gerencie compromissos e lembretes, com aviso automático por e-mail antes do vencimento.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm border-0 position-relative overflow-hidden">
                    <span class="position-absolute top-0 start-0 m-3 d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-75 text-white"
                        style="width: 44px; height: 44px; z-index: 2;">
                        <i class="bi bi-kanban fs-5"></i>
                    </span>
                    <img src="<?= baseUrl() ?>assets/img/home/gestao_Pr.jpg" class="card-img-top" alt="Gestão de Projetos">
                    <div class="card-body border-top border-primary border-3">
                        <h5 class="card-title">Gestão de Projetos</h5>
                        <p class="card-text text-muted small">Quadros Kanban, prazos e colaboração em tempo real com quem topar o projeto com você.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm border-0 position-relative overflow-hidden">
                    <span class="position-absolute top-0 start-0 m-3 d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-75 text-white"
                        style="width: 44px; height: 44px; z-index: 2;">
                        <i class="bi bi-cash-stack fs-5"></i>
                    </span>
                    <img src="<?= baseUrl() ?>assets/img/home/gestao_Fi.jpg" class="card-img-top" alt="Gestão Financeira">
                    <div class="card-body border-top border-success border-3">
                        <h5 class="card-title">Gestão Financeira</h5>
                        <p class="card-text text-muted small">Contas, cartão de crédito e faturas, com saldo calculado em tempo real.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm border-0 position-relative overflow-hidden">
                    <span class="position-absolute top-0 start-0 m-3 d-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-75 text-white"
                        style="width: 44px; height: 44px; z-index: 2;">
                        <i class="bi bi-bag-heart fs-5"></i>
                    </span>
                    <div class="card-img-top d-flex align-items-center justify-content-center"
                        style="height: 180px; background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);">
                        <i class="bi bi-bag-heart-fill text-white" style="font-size: 4rem; opacity: 0.85;"></i>
                    </div>
                    <div class="card-body border-top border-warning border-3">
                        <h5 class="card-title">Planos de Compra</h5>
                        <p class="card-text text-muted small">Planeje aquela compra em parcelas e acompanhe o progresso até concluir.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-12">
                <div class="rounded shadow-sm p-5 text-center" style="background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%); color: #fff;">
                    <h3 class="fw-bold mb-2">Pronto para colocar sua vida nos eixos?</h3>
                    <p class="mb-4 opacity-75">Leva menos de um minuto para criar sua conta.</p>
                    <a href="/Cadastro" class="btn btn-light btn-lg px-4">Criar minha conta gratuita</a>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/comuns/footer.php'; ?>
