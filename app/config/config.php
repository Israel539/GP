<?php 
    //url base da aplicação
    defined("BASEURL") || define("BASEURL", "http://gp/");

    //DEFINE AS CONFIGURAÇÕES PARA CESSAR A BASE DE DADOS
    defined("DB_CONF_CONEXAO") || define("DB_CONF_CONEXAO",[
        "DB_DRIVE" => "mysql",
        "DB_HOST" => "localhost",
        "DB_PORT" => 3306,
        "DB_USER" => "root",
        'DB_PSW' => '',
        'DB_BDADOS' => 'gpdb'
    ]);

    // ------------------------------------------------------------------
    // ENVIO DE E-MAIL (SMTP)
    // ------------------------------------------------------------------
    // Para Gmail: MAIL_HOST = smtp.gmail.com, MAIL_PORT = 587,
    // MAIL_SMTPSECURE = 'tls', MAIL_USER = seu e-mail completo,
    // MAIL_SENHA = uma "Senha de App" gerada na Conta Google (NAO a senha normal).
    //
    // Se MAIL_USER ficar vazio, o Mailer nao tenta enviar nada e so registra
    // um aviso no error_log -- assim quem ainda nao configurou SMTP consegue
    // desenvolver o resto do sistema sem o app quebrar por causa disso.
    defined("MAIL_CONF") || define("MAIL_CONF", [
        "MAIL_HOST"       => "smtp.gmail.com",
        "MAIL_PORT"       => 587,
        "MAIL_SMTPSECURE" => "tls",
        "MAIL_SMTP_AUTH"  => true,
        "MAIL_USER"       => "uussuarioladiesman217@gmail.com",
        "MAIL_SENHA"      => "grfafrwclprdoiqw",
        "MAIL_NOME"       => "Projeto GP",
    ]);

    // ------------------------------------------------------------------
    // ROTEAMENTO
    // ------------------------------------------------------------------

    defined("DEFAULT_CONTROLLER") || define("DEFAULT_CONTROLLER", "Home");
    defined("DEFAULT_METHOD")     || define("DEFAULT_METHOD", "index");

    defined("CONTROLLERS_PUBLICOS") || define("CONTROLLERS_PUBLICOS", [
        "Home",
        "Login",
        "Cadastro",
        "Contato",
    ]);

    defined("SESSION_USER_KEY") || define("SESSION_USER_KEY", "userLogado");

    // ------------------------------------------------------------------
    // PROTECAO CSRF
    // ------------------------------------------------------------------

    defined('CSRF_PROTECTION')   || define('CSRF_PROTECTION', true);
    defined('CSRF_TOKEN_NAME')   || define('CSRF_TOKEN_NAME', 'csrf_token');
    defined('CSRF_HEADER_NAME')  || define('CSRF_HEADER_NAME', 'X-CSRF-Token');
    defined('CSRF_EXPIRE')       || define('CSRF_EXPIRE', 7200);
    defined('CSRF_REGENERATE')   || define('CSRF_REGENERATE', false);

    defined('CSRF_EXCLUDE_URIS') || define('CSRF_EXCLUDE_URIS', []);

    // Janela (em horas) para permitir undo (restauracao) apos soft-delete
    defined('RESTORE_WINDOW_HOURS') || define('RESTORE_WINDOW_HOURS', 24);
