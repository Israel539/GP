<?php
$usuarioSessao = \App\Library\Session::get(SESSION_USER_KEY);
$estaLogado    = is_array($usuarioSessao);
$ehAdmin       = $estaLogado && (int) ($usuarioSessao['nivel'] ?? 0) === \App\Model\UsuarioModel::NIVEL_ADMIN;
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GP</title>
    <link rel="stylesheet" href="<?= baseUrl() ?>style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark static-top">
        <div class="container">
            <a class="navbar-brand" target="_blank" href="https://www.youtube.com/shorts/3fzc7cd8oiM?feature=share">
                <img src="<?= baseUrl() ?>assets/img/logo/logo.png" alt="Logo GP" height="36">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <?php
    $request = new \App\Library\Request();
    $controllerAtual = $request->getController();
    $metodoAtual = $request->getMetodo();
    $homeAtivo = $controllerAtual === 'Home' && $metodoAtual === 'index' ? 'active' : '';
    $contatoAtivo = $controllerAtual === 'Contato' || ($controllerAtual === 'Home' && $metodoAtual === 'contato') ? 'active' : '';
    $financeiroAtivo = $controllerAtual === 'Conta' ? 'active' : '';
    $comprasAtivo = $controllerAtual === 'PlanoCompra' ? 'active' : '';
    ?>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $homeAtivo ?>" href="/Home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $contatoAtivo ?>" href="/Contato">Contato</a>
                    </li>

                    <?php if ($estaLogado): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/Agenda">Agenda</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/Projeto">Projetos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $financeiroAtivo ?>" href="/Conta">Financeiro</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $comprasAtivo ?>" href="/PlanoCompra">Compras</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/Cartao">Cartoes</a>
                        </li>

                        <?php if ($ehAdmin): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarAdmin" role="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    Admin
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarAdmin">
                                    <li><a class="dropdown-item" href="/Admin">Painel</a></li>
                                    <li><a class="dropdown-item" href="/Admin/usuarios">Usuarios</a></li>
                                    <li><a class="dropdown-item" href="/Admin/projetos">Todos os projetos</a></li>
                                    <li><a class="dropdown-item" href="/Admin/suporte">Acesso de suporte</a></li>
                                    <li><a class="dropdown-item" href="/Admin/suporteHistorico">Log de auditoria</a></li>
                                    <li><a class="dropdown-item" href="/Admin/contatos">Mensagens de contato</a></li>
                                <li><a class="dropdown-item" href="/Admin/termos">Termos e Políticas</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <li class="nav-item d-flex align-items-center">
                            <a class="btn btn-outline-light btn-sm ms-2" href="/Login/logout">Sair</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/Login">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/Cadastro">Cadastro</a>
                        </li>
                    <?php endif; ?>

                    </ul>
                </div>
            </div>
        </div>
    </nav>