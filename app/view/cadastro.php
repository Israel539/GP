<?php include __DIR__ . '/comuns/header.php'; ?>
<!-- Cadastro do usúario -->

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <div class="card card-custom shadow-lg">
                    <div class="card-header card-header-custom text-center py-4">
                        <h3 class="mb-0">
                            <i class="bi bi-person-plus me-2"></i>Cadastro de usuario
                        </h3>
                        <p class="mb-0 mt-2 opacity-75">Preencha seus dados para criar sua conta</p>
                    </div>

                    <div class="card-body p-4">

                        <?= mensagens() ?>

                        <form action="/Cadastro/salvar" method="POST">

                        <?= \App\Library\Csrf::getHiddenField() ?>
                            <!-- informações de criação de conta -->

                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <label for="nome" class="form-label">Nome Completo </label>
                                    <input type="text" class="form-control" id="nome" name="nome"
                                        value="<?= valorAntigo('nome') ?>"
                                        placeholder="Digite seu nome completo" required>
                                    <?= campoErro('nome') ?>
                                </div>
                                <div class="col-md-4">
                                    <label for="cpf" class="form-label">CPF</label>
                                    <input type="text" class="form-control" id="cpf" name="cpf"
                                        value="<?= valorAntigo('cpf') ?>"
                                        placeholder="000.000.000-00" maxlength="14">
                                </div>
                            </div>




                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" id="email"
                                        value="<?= valorAntigo('email') ?>"
                                        placeholder="seu@email.com" required>
                                    <?= campoErro('email') ?>
                                </div>
                                <div class="col-md-4">
                                    <label for="data" class="form-label">Data Nascimento</label>
                                    <input type="date" class="form-control" id="data" name="data"
                                        value="<?= valorAntigo('data') ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="senha" class="form-label">Senha </label>
                                    <input type="password" class="form-control" id="senha" name="senha"
                                        placeholder="Digite uma senha" required>
                                    <?= campoErro('senha') ?>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirmar_senha" class="form-label">Confirmar Senha </label>
                                    <input type="password" class="form-control" id="confirmar_senha"
                                        name="confirmar_senha" placeholder="Digite a senha novamente" required>
                                    <?= campoErro('confirmar_senha') ?>
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Termos de Uso -->
                            <div class="mb-3 form-check">
                                <input class="form-check-input" type="checkbox" id="aceite_termos" name="aceite_termos"
                                    required>

                                <label class="form-check-label small" for="aceite_termos">
                                    Eu aceito os
                                    <a href="/Home/termoPolitica" target="_blank" class="text-decoration-none">
                                        Termos de Uso/Política de Privacidade
                                    </a>
                                    .
                                </label>
                                <?= campoErro('aceite_termos') ?>
                            </div>

                            <!-- Botões -->
                            <div class="d-flex justify-content-center align-items-center mb-3">
                                <a href="<?= baseUrl() ?>"
                                    class="btn btn-secondary btn-md text-decoration-none ps-4 pe-4">Voltar</a>&nbsp;&nbsp;
                                <button type="submit" class="btn btn-primary btn-md ps-4 pe-4">Entrar</button>
                            </div>

                            <!-- <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>Criar Conta
                                </button>
                                <a href="/Login" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Voltar para Login
                                </a>
                            </div> -->

                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
<?php include __DIR__ . '/comuns/footer.php'; ?>