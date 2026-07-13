<?php include __DIR__ . '/comuns/header.php'; ?>
    <!-- login -->
    <main class="container-fluid py-5">

        <div class="row">
            <div class="col-12 col-sm-12 col-md-6 mx-auto">
                <div class="card shadow-lg p-2 p-md-3">
                    <h3 class="text-center mb-4 card-title">Acesso à Conta</h3>

                    <form action="/Login/login" method="POST">

                        <?= \App\Library\Csrf::getHiddenField() ?>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email" placeholder="seu@email.com"
                                required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Senha</label>
                            <input type="password" class="form-control" name="password" id="password"
                                placeholder="********" required>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="termoPolitica">

                                <label class="form-check-label" for="termoPolitica">
                                    <div class="text-center small ">
                                        <a href="/Home/termoPolitica" target="_blank" class="text-decoration-none">Termo
                                            de uso/Política de privacidade</a>.
                                    </div>

                                </label>
                            </div>
                            <a href="#" class="text-decoration-none">Esqueci a senha?</a>
                        </div>

                        <div class="mb-3">
                            <?= mensagens() ?>
                        </div>

                        <div class="d-flex justify-content-center align-items-center mb-3">
                            <a href="<?= baseUrl() ?>"
                                class="btn btn-secondary btn-md text-decoration-none ps-4 pe-4">Voltar</a>&nbsp;&nbsp;
                            <button type="submit" class="btn btn-primary btn-md ps-4 pe-4">Entrar</button>
                        </div>
                    </form>

                    <hr>

                    <div class="text-center small mt-2">
                        Ao continuar, você concorda com nossos
                        <a href="/Home/termoPolitica" target="_blank" class="text-decoration-none">Termos de Uso
                            e Política de Privacidade</a>.
                    </div>

                    <div class="text-center mt-2">
                        Não tem uma conta? <a href="/Cadastro" class="text-decoration-none">Cadastre-se</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
<?php include __DIR__ . '/comuns/footer.php'; ?>