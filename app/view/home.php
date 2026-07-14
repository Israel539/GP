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
                <div class="p-4 rounded shadow-sm bg-gradient px-4 py-4" style="background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%); color: #fff;">
                    <h2 class="mb-2">Painel de Controle</h2>
                    <p class="mb-0">Resumo rápido do seu dia: projetos, finanças e compromissos.</p>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="/Projeto" class="btn btn-light btn-lg">Ir para Projetos</a>
            </div>
        </div>

        <?= mensagens() ?>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <span class="badge bg-secondary mb-3">Projetos</span>
                        <h1 class="display-5 mb-2"><?= (int) $totalProjetos ?></h1>
                        <p class="text-muted mb-3">Projetos ativos no seu workspace</p>
                        <a href="/Projeto" class="btn btn-outline-dark btn-sm">Ver projetos</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <span class="badge bg-secondary mb-3">Contas</span>
                        <h1 class="display-5 mb-2"><?= (int) $totalContas ?></h1>
                        <p class="text-muted mb-3">Contas financeiras configuradas</p>
                        <a href="/Conta" class="btn btn-outline-dark btn-sm">Ver contas</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <span class="badge bg-secondary mb-3">Compras</span>
                        <h1 class="display-5 mb-2"><?= (int) $totalPlanosCompra ?></h1>
                        <p class="text-muted mb-3">Planos de compra cadastrados</p>
                        <a href="/PlanoCompra" class="btn btn-outline-dark btn-sm">Ver planos</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <span class="badge bg-secondary mb-3">Saldo</span>
                        <h1 class="display-5 mb-2 <?= $saldoTotal < 0 ? 'text-danger' : 'text-success' ?>">R$ <?= number_format((float) $saldoTotal, 2, ',', '.') ?></h1>
                        <p class="text-muted mb-3">Saldo total consolidado</p>
                        <a href="/Conta" class="btn btn-outline-dark btn-sm">Detalhes</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Próximos compromissos</h5>
                    <small class="text-muted">Agenda dos próximos dias</small>
                </div>
                <a href="/Agenda" class="btn btn-sm btn-primary">Ver agenda completa</a>
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

        <div class="row mt-5 g-4">
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <img src="<?= baseUrl() ?>assets/img/home/agenda_Pe.jpg" class="card-img-top" alt="Agenda Pessoal">
                    <div class="card-body">
                        <h5 class="card-title">Agenda Pessoal</h5>
                        <p class="card-text">Gerencie seus compromissos e lembretes em um único lugar.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <img src="<?= baseUrl() ?>assets/img/home/gestao_Pr.jpg" class="card-img-top" alt="Gestão de Projetos">
                    <div class="card-body">
                        <h5 class="card-title">Gestão de Projetos</h5>
                        <p class="card-text">Acompanhe tarefas, prazos e a colaboração da equipe.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <img src="<?= baseUrl() ?>assets/img/home/gestao_Fi.jpg" class="card-img-top" alt="Gestão Financeira">
                    <div class="card-body">
                        <h5 class="card-title">Gestão Financeira</h5>
                        <p class="card-text">Controle receitas, despesas e sua saúde financeira.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/comuns/footer.php'; ?>
