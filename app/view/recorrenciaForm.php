<?php
/** @var array|null $recorrencia */
/** @var array $contas */
/** @var array $categorias */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <h3 class="mb-4"><?= !empty($recorrencia) ? 'Editar recorrência' : 'Nova recorrência' ?></h3>

            <?= mensagens() ?>

            <?php if (empty($contas)): ?>
                <div class="alert alert-warning">
                    Você precisa ter ao menos uma conta cadastrada. <a href="/Conta/form">Criar conta agora</a>.
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form action="<?= !empty($recorrencia) ? '/Recorrencia/atualizar' : '/Recorrencia/salvar' ?>" method="POST">
                            <?= \App\Library\Csrf::getHiddenField() ?>
                            <?php if (!empty($recorrencia)): ?>
                                <input type="hidden" name="id" value="<?= (int) $recorrencia['id'] ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label">Descrição</label>
                                <input type="text" name="descricao" class="form-control" placeholder="Ex: Aluguel"
                                    value="<?= htmlspecialchars($recorrencia['descricao'] ?? valorAntigo('descricao')) ?>" required>
                                <?= campoErro('descricao') ?>
                            </div>

                            <?php if (empty($recorrencia)): ?>
                                <div class="mb-3">
                                    <label class="form-label">Conta</label>
                                    <select name="conta_id" class="form-select" required>
                                        <?php foreach ($contas as $conta): ?>
                                            <option value="<?= (int) $conta['id'] ?>"><?= htmlspecialchars($conta['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Valor</label>
                                    <input type="text" name="valor" class="form-control" placeholder="0,00"
                                        value="<?= htmlspecialchars($recorrencia['valor'] ?? '') ?>" required>
                                    <?= campoErro('valor') ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tipo</label>
                                    <select name="tipo" class="form-select">
                                        <option value="despesa" <?= ($recorrencia['tipo'] ?? 'despesa') === 'despesa' ? 'selected' : '' ?>>Despesa</option>
                                        <option value="receita" <?= ($recorrencia['tipo'] ?? '') === 'receita' ? 'selected' : '' ?>>Receita</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Modalidade</label>
                                    <select name="modalidade" class="form-select">
                                        <?php foreach (['pix' => 'Pix', 'debito' => 'Débito', 'credito' => 'Crédito', 'dinheiro' => 'Dinheiro', 'outro' => 'Outro'] as $valor => $rotulo): ?>
                                            <option value="<?= $valor ?>" <?= ($recorrencia['modalidade'] ?? 'outro') === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Categoria</label>
                                    <select name="categoria_id" class="form-select">
                                        <option value="">-- sem categoria --</option>
                                        <?php foreach ($categorias as $cat): ?>
                                            <option value="<?= (int) $cat['id'] ?>" <?= (int) ($recorrencia['categoria_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Dia do mês pra lançar</label>
                                <input type="number" name="dia_mes" class="form-control" min="1" max="31"
                                    value="<?= htmlspecialchars($recorrencia['dia_mes'] ?? valorAntigo('dia_mes')) ?>" required>
                                <?= campoErro('dia_mes') ?>
                                <div class="form-text">Em meses mais curtos que esse dia, lança no último dia do mês.</div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Início da vigência</label>
                                    <input type="date" name="data_inicio" class="form-control"
                                        value="<?= htmlspecialchars($recorrencia['data_inicio'] ?? date('Y-m-d')) ?>" required>
                                    <?= campoErro('data_inicio') ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Fim (opcional)</label>
                                    <input type="date" name="data_fim" class="form-control"
                                        value="<?= htmlspecialchars($recorrencia['data_fim'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="/Recorrencia" class="btn btn-secondary">Voltar</a>
                                <button type="submit" class="btn btn-primary">Salvar</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
