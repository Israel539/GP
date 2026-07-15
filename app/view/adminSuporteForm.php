<?php include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <h2 class="mb-3">Acesso de suporte</h2>
            <p class="text-muted">
                Use isso só quando um usuário pedir ajuda com um problema específico.
                O acesso é registrado permanentemente no <a href="/Admin/suporteHistorico">log de auditoria</a>,
                vale só para o recurso informado, e expira em 15 minutos.
            </p>

            <?= mensagens() ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="/Admin/suporteAcessar" method="POST">
                        <?= \App\Library\Csrf::getHiddenField() ?>

                        <div class="mb-3">
                            <label class="form-label">Tipo de recurso</label>
                            <select name="tipo_recurso" class="form-select" required>
                                <option value="projeto">Projeto (kanban)</option>
                                <option value="conta">Conta (extrato)</option>
                                <option value="cartao">Cartão (faturas)</option>
                                <option value="fatura">Fatura (detalhe)</option>
                                <option value="compromisso">Compromisso (agenda)</option>
                                <option value="plano_compra">Plano de compra</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ID do recurso</label>
                            <input type="number" name="recurso_id" class="form-control" required min="1">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Motivo (mínimo 10 caracteres, fica registrado)</label>
                            <textarea name="motivo" class="form-control" rows="3" required minlength="10"
                                placeholder="Ex: usuário abriu chamado #123 relatando saldo incorreto"></textarea>
                        </div>

                        <button type="submit" class="btn btn-warning">Conceder acesso por 15 min</button>
                        <a href="/Admin" class="btn btn-secondary">Voltar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
