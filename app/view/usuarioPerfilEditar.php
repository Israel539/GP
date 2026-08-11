<?php
/** @var array $usuario */
include __DIR__ . '/comuns/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <a href="/Usuario/perfil" class="btn btn-outline-secondary btn-sm mb-3">
                <i class="bi bi-arrow-left"></i> Voltar para o perfil
            </a>

            <?= mensagens() ?>

            <!-- Dados de perfil -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-person-gear"></i> Editar dados do perfil
                </div>
                <div class="card-body">
                    <form action="/Usuario/atualizar" method="POST" enctype="multipart/form-data">
                        <?= \App\Library\Csrf::getHiddenField() ?>

                        <div class="d-flex align-items-center mb-4">
                            <?php if (!empty($usuario['foto'])): ?>
                                <img src="<?= htmlspecialchars($usuario['foto']) ?>" alt="Foto de perfil"
                                    class="rounded-circle me-3" style="width: 80px; height: 80px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary bg-opacity-25 d-flex align-items-center justify-content-center me-3"
                                    style="width: 80px; height: 80px;">
                                    <i class="bi bi-person-fill" style="font-size: 2rem;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <label for="foto_arquivo" class="form-label mb-1">Foto de perfil</label>
                                <input type="file" class="form-control form-control-sm" id="foto_arquivo"
                                    name="foto_arquivo" accept="image/jpeg,image/png,image/webp">
                                <div class="form-text">JPG, PNG ou WEBP, até 5MB.</div>
                                <?php if (!empty($usuario['foto'])): ?>
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="checkbox" id="remover_foto" name="remover_foto" value="1">
                                        <label class="form-check-label small text-muted" for="remover_foto">
                                            Remover foto atual
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="nome" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" id="nome" name="nome"
                                    value="<?= valorAntigo('nome', $usuario['nome'] ?? '') ?>" required>
                                <?= campoErro('nome') ?>
                            </div>
                            <div class="col-md-4">
                                <label for="cpf" class="form-label">CPF</label>
                                <input type="text" class="form-control" id="cpf" name="cpf"
                                    value="<?= valorAntigo('cpf', $usuario['cpf'] ?? '') ?>"
                                    placeholder="000.000.000-00" maxlength="14">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?= valorAntigo('email', $usuario['email'] ?? '') ?>" required>
                                <?= campoErro('email') ?>
                            </div>
                            <div class="col-md-4">
                                <label for="data_nascimento" class="form-label">Data de nascimento</label>
                                <input type="date" class="form-control" id="data_nascimento" name="data_nascimento"
                                    value="<?= valorAntigo('data_nascimento', $usuario['data_nascimento'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="telefone_whats" class="form-label">WhatsApp</label>
                                <input type="text" class="form-control" id="telefone_whats" name="telefone_whats"
                                    value="<?= valorAntigo('telefone_whats', $usuario['telefone_whats'] ?? '') ?>"
                                    placeholder="(00) 00000-0000">
                                <div class="form-text">Usado pra mandar lembrete de compromisso (RN03).</div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Salvar alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Troca de senha -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-shield-lock"></i> Alterar senha
                </div>
                <div class="card-body">
                    <form action="/Usuario/alterarSenha" method="POST">
                        <?= \App\Library\Csrf::getHiddenField() ?>

                        <div class="mb-3">
                            <label for="senha_atual" class="form-label">Senha atual</label>
                            <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
                            <?= campoErro('senha_atual') ?>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nova_senha" class="form-label">Nova senha</label>
                                <input type="password" class="form-control" id="nova_senha" name="nova_senha" required>
                                <?= campoErro('nova_senha') ?>
                            </div>
                            <div class="col-md-6">
                                <label for="confirmar_nova_senha" class="form-label">Confirmar nova senha</label>
                                <input type="password" class="form-control" id="confirmar_nova_senha" name="confirmar_nova_senha" required>
                                <?= campoErro('confirmar_nova_senha') ?>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bi bi-key"></i> Trocar senha
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/comuns/footer.php'; ?>
