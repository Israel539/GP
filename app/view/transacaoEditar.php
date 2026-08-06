<?php
/**
 * @var array $conta
 * @var array $transacao
 * @var array $categorias
 * @var array $tags
 * @var array|null $cartao
 */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2>Editar transacao</h2>
            <p class="text-muted">Conta: <?= htmlspecialchars($conta['nome']) ?></p>
        </div>
        <div class="col-md-4 text-end">
            <a href="/Transacao/extrato/<?= (int) $conta['id'] ?>" class="btn btn-outline-secondary btn-sm">Voltar ao extrato</a>
        </div>
    </div>

    <?= mensagens() ?>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <form action="/Transacao/atualizar/<?= (int) $transacao['id'] ?>" method="POST" id="formEditarTransacao">
                        <?= \App\Library\Csrf::getHiddenField() ?>

                        <div class="mb-3">
                            <label class="form-label small">Descricao</label>
                            <input type="text" name="descricao" class="form-control form-control-sm"
                                value="<?= htmlspecialchars(valorAntigo('descricao', $transacao['descricao'])) ?>" required>
                            <?= campoErro('descricao') ?>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small">Valor</label>
                                <input type="text" name="valor" class="form-control form-control-sm"
                                    value="<?= htmlspecialchars(valorAntigo('valor', number_format((float) $transacao['valor'], 2, ',', '.'))) ?>" required>
                                <?= campoErro('valor') ?>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Tipo</label>
                                <select name="tipo" class="form-select form-select-sm">
                                    <option value="despesa" <?= ($transacao['tipo'] ?? '') === 'despesa' ? 'selected' : '' ?>>Despesa</option>
                                    <option value="receita" <?= ($transacao['tipo'] ?? '') === 'receita' ? 'selected' : '' ?>>Receita</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Modalidade</label>
                            <select name="modalidade" id="modalidadeEditar" class="form-select form-select-sm" onchange="alternarCampoCartaoEditar()" <?= $transacao['modalidade'] === 'credito' ? 'disabled' : '' ?>>
                                <?php foreach (['pix' => 'Pix', 'boleto' => 'Boleto', 'debito' => 'Debito', 'credito' => 'Credito', 'dinheiro' => 'Dinheiro', 'outro' => 'Outro'] as $valor => $rotulo): ?>
                                    <option value="<?= $valor ?>" <?= ($transacao['modalidade'] ?? '') === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($transacao['modalidade'] === 'credito'): ?>
                                <div class="form-text">Modalidade de credito nao pode ser alterada aqui.</div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3" id="campoCartaoEditar" style="display: <?= $transacao['modalidade'] === 'credito' ? 'block' : 'none' ?>;">
                            <label class="form-label small">Cartao</label>
                            <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($cartao['nome'] ?? 'Nenhum cartao') ?>" disabled>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small">Data do fato</label>
                                <input type="date" name="data_fato_gerador" class="form-control form-control-sm"
                                    value="<?= htmlspecialchars(valorAntigo('data_fato_gerador', $transacao['data_fato_gerador'])) ?>" required>
                                <?= campoErro('data_fato_gerador') ?>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Competencia</label>
                                <input type="date" name="data_competencia" class="form-control form-control-sm"
                                    value="<?= htmlspecialchars(valorAntigo('data_competencia', $transacao['data_competencia'])) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Categoria <a href="/Categoria" class="small">(gerenciar)</a></label>
                            <select name="categoria_id" class="form-select form-select-sm">
                                <option value="">-- sem categoria --</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= (int) $cat['id'] ?>" <?= ((int) ($transacao['categoria_id'] ?? 0) === (int) $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nome']) ?> (<?= $cat['tipo'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Tags (separadas por virgula)</label>
                            <input type="text" name="tags" class="form-control form-control-sm"
                                value="<?= htmlspecialchars(valorAntigo('tags', implode(', ', array_column($tags, 'nome')))) ?>">
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100">Salvar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function alternarCampoCartaoEditar() {
    var modalidade = document.getElementById('modalidadeEditar').value;
    document.getElementById('campoCartaoEditar').style.display = (modalidade === 'credito') ? 'block' : 'none';
}
</script>

<?php include __DIR__ . '/comuns/footer.php'; ?>
