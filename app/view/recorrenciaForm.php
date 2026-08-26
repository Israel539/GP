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
                                <div class="mb-3" id="campoContaRecorrencia">
                                    <label class="form-label">Conta</label>
                                    <select name="conta_id" id="contaIdRecorrencia" class="form-select" required>
                                        <?php foreach ($contas as $conta): ?>
                                            <option value="<?= (int) $conta['id'] ?>"><?= htmlspecialchars($conta['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text" id="dicaContaDinheiro" style="display: none;">
                                        Recorrencia em dinheiro nao precisa de conta -- ela usa automaticamente o seu "Dinheiro Fisico".
                                    </div>
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
                                    <select name="modalidade" id="modalidadeRecorrencia" class="form-select" onchange="alternarCampoCartaoRecorrencia()">
                                        <?php foreach (['pix' => 'Pix', 'boleto' => 'Boleto', 'debito' => 'Débito', 'credito' => 'Crédito', 'dinheiro' => 'Dinheiro', 'outro' => 'Outro'] as $valor => $rotulo): ?>
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

                            <div class="mb-3" id="campoCartaoRecorrencia" style="display: <?= ($recorrencia['modalidade'] ?? '') === 'credito' ? 'block' : 'none' ?>;">
                                <label class="form-label">Cartao (obrigatorio p/ credito)</label>
                                <select name="cartao_id" class="form-select">
                                    <option value="">-- selecione --</option>
                                    <?php foreach ($cartoes as $cartao): ?>
                                        <option value="<?= (int) $cartao['id'] ?>"
                                            <?= (int) ($recorrencia['cartao_id'] ?? 0) === (int) $cartao['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cartao['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
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

<script>
function alternarCampoCartaoRecorrencia() {
    var modalidade = document.getElementById('modalidadeRecorrencia').value;
    document.getElementById('campoCartaoRecorrencia').style.display = (modalidade === 'credito') ? 'block' : 'none';

    // RN10 (Dinheiro Fisico): esse campo so existe na tela de CRIACAO (na
    // edicao a conta ja fica fixa e nem aparece) -- por isso o "if" de
    // seguranca, pra nao quebrar quando a funcao roda na tela de editar.
    var campoConta = document.getElementById('campoContaRecorrencia');
    if (campoConta) {
        var selectConta = document.getElementById('contaIdRecorrencia');
        var dicaDinheiro = document.getElementById('dicaContaDinheiro');
        var ehDinheiro = (modalidade === 'dinheiro');

        selectConta.style.display = ehDinheiro ? 'none' : 'block';
        selectConta.required = !ehDinheiro;
        dicaDinheiro.style.display = ehDinheiro ? 'block' : 'none';
    }
}

// Garante o estado certo se a pagina carregar com "Dinheiro" ja selecionado
// (ex: usuario voltou pra essa tela com o navegador).
document.addEventListener('DOMContentLoaded', alternarCampoCartaoRecorrencia);
</script>

<?php include __DIR__ . '/comuns/footer.php'; ?>
