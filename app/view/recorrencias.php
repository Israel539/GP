<?php
/** @var array $recorrencias */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-8">
            <h2>Transações Recorrentes</h2>
            <p class="text-muted small mb-0">Contas fixas (aluguel, assinatura) lançadas automaticamente todo mês.</p>
        </div>
        <div class="col-4 text-end">
            <form action="/Recorrencia/gerarAgora" method="POST" class="d-inline">
                <?= \App\Library\Csrf::getHiddenField() ?>
                <button type="submit" class="btn btn-outline-success">
                    <i class="bi bi-arrow-repeat"></i> Gerar agora
                </button>
            </form>
            <a href="/Recorrencia/form" class="btn btn-primary">+ Nova recorrência</a>
        </div>
    </div>

    <p class="text-muted small">
        "Gerar agora" roda a mesma varredura do agendamento automatico (cron) na hora,
        pra quem quiser lançar uma recorrência pendente sem esperar.
    </p>

    <?= mensagens() ?>

    <?php if (empty($recorrencias)): ?>
        <div class="alert alert-secondary">Você ainda não cadastrou nenhuma transação recorrente.</div>
    <?php else: ?>
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Conta</th>
                    <th>Categoria</th>
                    <th>Valor</th>
                    <th>Dia</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recorrencias as $r): ?>
                    <tr class="<?= !$r['ativo'] ? 'text-muted' : '' ?>">
                        <td><?= htmlspecialchars($r['descricao']) ?></td>
                        <td><?= htmlspecialchars($r['conta_nome']) ?></td>
                        <td><?= htmlspecialchars($r['categoria_nome'] ?? '-') ?></td>
                        <td class="<?= $r['tipo'] === 'despesa' ? 'text-danger' : 'text-success' ?>">
                            <?= $r['tipo'] === 'despesa' ? '-' : '+' ?> R$ <?= number_format((float) $r['valor'], 2, ',', '.') ?>
                        </td>
                        <td>Todo dia <?= (int) $r['dia_mes'] ?></td>
                        <td>
                            <?php if ($r['ativo']): ?>
                                <span class="badge bg-success">Ativa</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Pausada</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="/Recorrencia/editar/<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                            <form action="/Recorrencia/alternar/<?= (int) $r['id'] ?>" method="POST" class="d-inline">
                                <?= \App\Library\Csrf::getHiddenField() ?>
                                <button type="submit" class="btn btn-sm btn-outline-warning"><?= $r['ativo'] ? 'Pausar' : 'Reativar' ?></button>
                            </form>
                            <form action="/Recorrencia/excluir/<?= (int) $r['id'] ?>" method="POST" class="d-inline">
                                <?= \App\Library\Csrf::getHiddenField() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Excluir esta recorrência? Isso NAO apaga as transações já lançadas.')">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
