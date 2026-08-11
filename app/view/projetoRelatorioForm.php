<?php
/** @var array $projeto */
/** @var array $relatorio */
/** @var array $historico */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-4">

    <a href="/Projeto/kanban/<?= (int) $projeto['id'] ?>" class="btn btn-outline-secondary btn-sm mb-3">
        <i class="bi bi-arrow-left"></i> Voltar para o projeto
    </a>

    <div class="row mb-3 align-items-center">
        <div class="col-8">
            <h2>Relatório do projeto</h2>
            <span class="text-muted"><?= htmlspecialchars($projeto['nome']) ?></span>
        </div>
        <div class="col-4 text-end">
            <?php if (!empty($relatorio)): ?>
                <a href="/ProjetoRelatorio/exportarPdf/<?= (int) $projeto['id'] ?>" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
                </a>
                <a href="/ProjetoRelatorio/exportarDocx/<?= (int) $projeto['id'] ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-earmark-word"></i> Exportar Word
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?= mensagens() ?>

    <div class="row">
        <!-- Formulario do relatorio, em secoes guiadas -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    Conteúdo do relatório
                    <?php if (!empty($relatorio)): ?>
                        <span class="text-muted small float-end">
                            Última edição: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($relatorio['atualizado_em']))) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form action="/ProjetoRelatorio/salvar/<?= (int) $projeto['id'] ?>" method="POST">
                        <?= \App\Library\Csrf::getHiddenField() ?>

                        <div class="mb-3">
                            <label class="form-label">Contexto <span class="text-muted small">(opcional)</span></label>
                            <textarea name="contexto" class="form-control" rows="3"
                                placeholder="Qual era o objetivo do projeto? Por que ele foi criado?"><?= valorAntigo('contexto', $relatorio['contexto'] ?? '') ?></textarea>
                            <?= campoErro('contexto') ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">O que foi feito <span class="text-danger">*</span></label>
                            <textarea name="o_que_foi_feito" class="form-control" rows="6" minlength="10" required
                                placeholder="O que foi realizado ao longo do projeto? Principais entregas e resultados."><?= valorAntigo('o_que_foi_feito', $relatorio['o_que_foi_feito'] ?? '') ?></textarea>
                            <?= campoErro('o_que_foi_feito') ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Decisões tomadas <span class="text-muted small">(opcional)</span></label>
                            <textarea name="decisoes" class="form-control" rows="4"
                                placeholder="Alguma decisão importante foi tomada durante o projeto? Por quê?"><?= valorAntigo('decisoes', $relatorio['decisoes'] ?? '') ?></textarea>
                            <?= campoErro('decisoes') ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Próximos passos <span class="text-muted small">(opcional)</span></label>
                            <textarea name="proximos_passos" class="form-control" rows="4"
                                placeholder="O que ainda falta, ou o que vem depois deste projeto?"><?= valorAntigo('proximos_passos', $relatorio['proximos_passos'] ?? '') ?></textarea>
                            <?= campoErro('proximos_passos') ?>
                        </div>

                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Ao exportar, o histórico do projeto (participantes, tarefas e chat) é incluído automaticamente junto com este texto.
                            </small>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-save"></i> Salvar relatório
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Previa do historico (resumo + timeline agrupada por dia), so como referencia -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i> Histórico do projeto
                </div>
                <div class="card-body p-0">
                    <div style="max-height: 560px; overflow-y: auto;" class="p-3">

                        <?php if (empty($historico['resumo'])): ?>
                            <p class="text-muted small mb-0">
                                Nenhum evento registrado ainda. A partir de agora, ações como criar/mover tarefas,
                                mensagens no chat e entrada/saída de colaboradores aparecem aqui.
                            </p>
                        <?php else: ?>

                            <ul class="list-unstyled small mb-3">
                                <?php foreach ($historico['resumo'] as $item): ?>
                                    <li class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                        <span><?= htmlspecialchars($item['rotulo']) ?></span>
                                        <strong><?= (int) $item['quantidade'] ?></strong>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <?php if ($historico['truncado']): ?>
                                <p class="text-muted" style="font-size: 0.75rem;">
                                    Mostrando os eventos mais recentes de <?= (int) $historico['total_eventos'] ?> no total.
                                    O resumo acima conta todos.
                                </p>
                            <?php endif; ?>

                            <?php foreach (array_reverse($historico['dias']) as $dia): ?>
                                <div class="mb-2">
                                    <div class="fw-bold" style="font-size: 0.8rem;">
                                        <?= htmlspecialchars($dia['data_label']) ?>
                                    </div>
                                    <ul class="list-unstyled small mb-0">
                                        <?php foreach (array_reverse($dia['itens']) as $evento): ?>
                                            <li class="mb-2 pb-2 border-bottom">
                                                <div class="text-muted" style="font-size: 0.72rem;">
                                                    <?= htmlspecialchars(date('H:i', strtotime($evento['data']))) ?>
                                                </div>
                                                <div><?= htmlspecialchars($evento['texto']) ?></div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
