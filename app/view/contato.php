<?php include __DIR__ . '/comuns/header.php'; ?>
 <!-- Page Content -->
    <!-- contato -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <div class="card card-custom shadow-lg">
                    <div class="card-header card-header-custom text-center py-4">
                        <h3 class="mb-0">
                            <i class="bi bi-envelope-at me-2"></i>Entre em Contato
                        </h3>
                        <p class="mb-0 mt-2 opacity-75">Envie sua mensagem e responderemos em breve</p>
                    </div>

                    <div class="card-body p-4">
                        <form action="#" method="POST">

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nome_contato" class="form-label">Nome Completo</label>
                                    <input type="text" class="form-control" id="nome_contato" name="nome" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email_contato" class="form-label">E-mail</label>
                                    <input type="email" class="form-control" id="email_contato" name="email"
                                        placeholder="seu@email.com" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="assunto" class="form-label">Assunto</label>
                                <input type="text" class="form-control" id="assunto" name="assunto"
                                    placeholder="Ex: Dúvida sobre o sistema" required>
                            </div>

                            <div class="mb-3">
                                <label for="mensagem" class="form-label">Sua Mensagem</label>
                                <textarea class="form-control" id="mensagem" name="mensagem" rows="4"
                                    placeholder="Escreva aqui detalhadamente o que você precisa..." required></textarea>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-center align-items-center mb-3">
                                <a href="<?= baseUrl() ?>"
                                    class="btn btn-secondary btn-md text-decoration-none ps-4 pe-4">Voltar</a>
                                &nbsp;&nbsp;
                                <button type="submit" class="btn btn-primary btn-md ps-4 pe-4">
                                    <i class="bi bi-send me-2"></i>Enviar Mensagem
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
<?php include __DIR__ . '/comuns/footer.php'; ?>