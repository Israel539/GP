<?php
/** @var array<string,mixed>|null $compromisso */
/** @var string|null $dataPreenchida */
$compromisso = $compromisso ?? null;
$dataPreenchida = $dataPreenchida ?? null;
include __DIR__ . '/comuns/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-lg">
                <div class="card-body p-4">
                    <h3 class="mb-4"><?= $compromisso ? 'Editar compromisso' : 'Novo compromisso' ?></h3>

                    <?= mensagens() ?>

                    <form action="/Agenda/salvar" method="POST">
                        <?= \App\Library\Csrf::getHiddenField() ?>
                        <?php if ($compromisso): ?>
                            <input type="hidden" name="id" value="<?= (int) $compromisso['id'] ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="titulo" class="form-label">Titulo</label>
                            <input type="text" class="form-control" id="titulo" name="titulo"
                                value="<?= htmlspecialchars($compromisso['titulo'] ?? valorAntigo('titulo')) ?>" required>
                            <?= campoErro('titulo') ?>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descricao</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="2"><?= htmlspecialchars($compromisso['descricao'] ?? valorAntigo('descricao')) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <?php
                                    $tipoAtual = $compromisso['tipo'] ?? 'outro';
                                    $tipos = [
                                        'reuniao_presencial' => 'Reuniao / compromisso presencial',
                                        'tarefa_pessoal'     => 'Tarefa pessoal',
                                        'lembrete'           => 'Lembrete',
                                        'outro'              => 'Outro',
                                    ];
                                ?>
                                <?php foreach ($tipos as $valor => $rotulo): ?>
                                    <option value="<?= $valor ?>" <?= $tipoAtual === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                "Reuniao / compromisso presencial" nao pode se sobrepor a outro do mesmo tipo no mesmo horario (RN01).
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="data_inicio" class="form-label">Inicio</label>
                                <input type="datetime-local" class="form-control" id="data_inicio" name="data_inicio"
                                    value="<?= $compromisso ? date('Y-m-d\TH:i', strtotime($compromisso['data_inicio'])) : (!empty($dataPreenchida) ? htmlspecialchars($dataPreenchida) . 'T09:00' : '') ?>" required>
                                <?= campoErro('data_inicio') ?>
                            </div>
                            <div class="col-md-6">
                                <label for="data_fim" class="form-label">Termino</label>
                                <input type="datetime-local" class="form-control" id="data_fim" name="data_fim"
                                    value="<?= $compromisso ? date('Y-m-d\TH:i', strtotime($compromisso['data_fim'])) : '' ?>" required>
                                <?= campoErro('data_fim') ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="local" class="form-label">Local (opcional)</label>
                            <input type="text" class="form-control" id="local" name="local"
                                value="<?= htmlspecialchars($compromisso['local'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Notificar antes de vencer</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notificar_email" name="notificar_email" value="1"
                                    <?= !empty($compromisso['notificar_email']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="notificar_email">E-mail</label>
                            </div>
                            <div class="form-text">O aviso e enviado 1 dia antes do horario de inicio (RN03).</div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="/Agenda" class="btn btn-secondary">Voltar</a>
                            <button type="submit" class="btn btn-primary"><?= $compromisso ? 'Salvar alteracoes' : 'Criar compromisso' ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
