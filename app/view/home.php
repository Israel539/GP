<?php include __DIR__ . '/comuns/header.php'; ?>

<?php if (!empty($estaLogado)): ?>

    <!-- Dashboard de quem esta logado -->
    <div class="container py-5">
        <h2 class="mb-4">Ola, <?= htmlspecialchars($nome) ?>!</h2>

        <?= mensagens() ?>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <h1><?= (int) $totalProjetos ?></h1>
                        <p class="text-muted mb-2">Projeto(s)</p>
                        <a href="/Projeto" class="btn btn-sm btn-outline-primary">Ver projetos</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <h1><?= (int) $totalContas ?></h1>
                        <p class="text-muted mb-2">Conta(s) financeira(s)</p>
                        <a href="/Conta" class="btn btn-sm btn-outline-primary">Ver contas</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <h1 class="<?= $saldoTotal < 0 ? 'text-danger' : 'text-success' ?>">
                            R$ <?= number_format((float) $saldoTotal, 2, ',', '.') ?>
                        </h1>
                        <p class="text-muted mb-2">Saldo total (RN08)</p>
                        <a href="/Conta" class="btn btn-sm btn-outline-primary">Detalhes</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                Proximos compromissos
                <a href="/Agenda" class="btn btn-sm btn-primary">Ver agenda completa</a>
            </div>
            <?php if (empty($proximosCompromissos)): ?>
                <div class="card-body">
                    <p class="text-muted mb-0">Nenhum compromisso pendente nos proximos dias.</p>
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
    <div class="container">
        <h1 class="mt-4">GP</h1>
        <p>O sistema que você precisa para colocar sua vida nos eixos.</p>
    </div>

    <div class="container py-5">
        <div class="row justify-content-center g-4">
            <h1>Modelos Disponiveis do no Sistema</h1>

            <div class="col-md-4 d-flex justify-content-center">
                <div class="card" style="width: 18rem;">
                    <img src="assets/img/home/agenda_Pe.jpg" class="card-img-top" alt="...">
                    <div class="card-body">
                        <h5 class="card-title">Agenda Pessoal</h5>
                        <h6 class="card-subtitle mb-2 text-body-secondary">Organize seu tempo</h6>
                        <p class="card-text">
                            Gerencie seus compromissos diários, reuniões e tarefas em um só lugar. Receba notificações
                            personalizadas e nunca mais perca um prazo importante.
                        </p>
                        <hr>
                        <p>Faça login para ter acesso às funcionalidades do sistema ou cadastre-se.</p>
                        <a href="/Login" class="card-link">Login</a>
                        <a href="/Cadastro" class="card-link">Cadastro</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 d-flex justify-content-center">
                <div class="card" style="width: 18rem;">
                    <img src="assets/img/home/gestao_Pr.jpg" class="card-img-top" alt="...">
                    <div class="card-body">
                        <h5 class="card-title">Gestão de Projetos</h5>
                        <h6 class="card-subtitle mb-2 text-body-secondary">Gerencie seus projetos</h6>
                        <p class="card-text">
                            Controle o progresso de suas metas, atribua tarefas aos membros da equipe e visualize o
                            cronograma completo através de quadros Kanban dinâmicos.
                        </p>
                        <hr>
                        <p>Faça login para ter acesso às funcionalidades do sistema ou cadastre-se.</p>
                        <a href="/Login" class="card-link">Login</a>
                        <a href="/Cadastro" class="card-link">Cadastro</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 d-flex justify-content-center">
                <div class="card" style="width: 18rem;">
                    <img src="assets/img/home/gestao_Fi.jpg" class="card-img-top" alt="...">
                    <div class="card-body">
                        <h5 class="card-title">Gestão Financeira</h5>
                        <h6 class="card-subtitle mb-2 text-body-secondary">Controle seus gastos</h6>
                        <p class="card-text">
                            Acompanhe seu fluxo de caixa, registre entradas e saídas e gere relatórios detalhados para
                            manter sua saúde financeira sempre em dia.
                        </p>
                        <hr>
                        <p>Faça login para ter acesso às funcionalidades do sistema ou cadastre-se.</p>
                        <a href="/Login" class="card-link">Login</a>
                        <a href="/Cadastro" class="card-link">Cadastro</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/comuns/footer.php'; ?>
