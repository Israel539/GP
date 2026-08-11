<?php
/** @var array|null $recorrencia */
include __DIR__ . '/comuns/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?= $recorrencia ? 'Editar atividade recorrente' : 'Nova atividade recorrente' ?></h5>
                </div>
                <div class="card-body">
                    <?= mensagens() ?>

                    <form action="<?= $recorrencia ? "/CompromissoRecorrente/atualizar/{$recorrencia['id']}" : '/CompromissoRecorrente/salvar' ?>" method="POST">
                        <?= \App\Library\Csrf::getHiddenField() ?>

                        <div class="mb-3">
                            <label class="form-label">Título</label>
                            <input type="text" name="titulo" class="form-control"
                                value="<?= htmlspecialchars($recorrencia['titulo'] ?? valorAntigo('titulo')) ?>"
                                placeholder="Ex: Aula de Gestão de Projetos" required minlength="2" maxlength="150">
                            <?= campoErro('titulo') ?>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tipo</label>
                                <select name="tipo" class="form-select">
                                    <?php
                                        $tipoAtual = $recorrencia['tipo'] ?? 'outro';
                                        $tipos = [
                                            'reuniao_presencial' => 'Reunião / compromisso presencial',
                                            'tarefa_pessoal'     => 'Tarefa pessoal',
                                            'lembrete'           => 'Lembrete',
                                            'outro'              => 'Outro',
                                        ];
                                    ?>
                                    <?php foreach ($tipos as $valor => $rotulo): ?>
                                        <option value="<?= $valor ?>" <?= $tipoAtual === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Local (opcional)</label>
                                <input type="text" name="local" class="form-control"
                                    value="<?= htmlspecialchars($recorrencia['local'] ?? '') ?>" placeholder="Ex: Sala 12, Bloco B">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Dia da semana</label>
                            <select name="dia_semana" class="form-select">
                                <?php
                                    $diaAtual = $recorrencia['dia_semana'] ?? '1';
                                    $nomesDia = [0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado'];
                                ?>
                                <?php foreach ($nomesDia as $valor => $rotulo): ?>
                                    <option value="<?= $valor ?>" <?= (int) $diaAtual === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= campoErro('dia_semana') ?>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Hora de início</label>
                                <input type="time" name="hora_inicio" class="form-control"
                                    value="<?= htmlspecialchars(substr($recorrencia['hora_inicio'] ?? '', 0, 5)) ?>" required>
                                <?= campoErro('hora_inicio') ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hora de término</label>
                                <input type="time" name="hora_fim" class="form-control"
                                    value="<?= htmlspecialchars(substr($recorrencia['hora_fim'] ?? '', 0, 5)) ?>" required>
                                <?= campoErro('hora_fim') ?>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Válido a partir de</label>
                                <input type="date" name="data_inicio" class="form-control"
                                    value="<?= htmlspecialchars($recorrencia['data_inicio'] ?? date('Y-m-d')) ?>" required>
                                <?= campoErro('data_inicio') ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Válido até (opcional)</label>
                                <input type="date" name="data_fim" class="form-control"
                                    value="<?= htmlspecialchars($recorrencia['data_fim'] ?? '') ?>">
                                <div class="form-text">Ex: fim do semestre. Deixe vazio pra continuar indefinidamente.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <textarea name="descricao" class="form-control" rows="2"
                                placeholder="Anotações (opcional)"><?= htmlspecialchars($recorrencia['descricao'] ?? '') ?></textarea>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="notificar_email" name="notificar_email" value="1"
                                <?= ($recorrencia === null || !empty($recorrencia['notificar_email'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="notificar_email">Notificar por e-mail 1 dia antes de cada ocorrência</label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="/CompromissoRecorrente" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
