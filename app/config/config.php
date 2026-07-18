<?php
    // Carrega o .env (se existir) para dentro de $_ENV/getenv() antes de
    // definir qualquer constante -- e por isso que public/index.php agora
    // exige o vendor/autoload.php ANTES deste arquivo (Env e uma classe
    // autoloadada via PSR-4).
    \App\Library\Env::carregar(__DIR__ . '/../../.env');

    //url base da aplicação
    defined("BASEURL") || define("BASEURL", \App\Library\Env::get('BASEURL', 'http://gp/'));

    //DEFINE AS CONFIGURAÇÕES PARA CESSAR A BASE DE DADOS
    defined("DB_CONF_CONEXAO") || define("DB_CONF_CONEXAO",[
        "DB_DRIVE" => \App\Library\Env::get('DB_DRIVE', 'mysql'),
        "DB_HOST" => \App\Library\Env::get('DB_HOST', 'localhost'),
        "DB_PORT" => (int) \App\Library\Env::get('DB_PORT', 3306),
        "DB_USER" => \App\Library\Env::get('DB_USER', 'root'),
        'DB_PSW' => \App\Library\Env::get('DB_PSW', ''),
        'DB_BDADOS' => \App\Library\Env::get('DB_BDADOS', 'gpdb')
    ]);

    // ------------------------------------------------------------------
    // ENVIO DE E-MAIL (SMTP)
    // ------------------------------------------------------------------
    // Os valores reais (usuario/senha) agora vem do .env, nunca ficam
    // escritos aqui no codigo-fonte (que pode ir pro Git). Ver .env.example
    // para o que precisa preencher.
    //
    // Se MAIL_USER ficar vazio, o Mailer nao tenta enviar nada e so registra
    // um aviso no error_log -- assim quem ainda nao configurou SMTP consegue
    // desenvolver o resto do sistema sem o app quebrar por causa disso.
    defined("MAIL_CONF") || define("MAIL_CONF", [
        "MAIL_HOST"       => \App\Library\Env::get('MAIL_HOST', 'smtp.gmail.com'),
        "MAIL_PORT"       => (int) \App\Library\Env::get('MAIL_PORT', 587),
        "MAIL_SMTPSECURE" => \App\Library\Env::get('MAIL_SMTPSECURE', 'tls'),
        "MAIL_SMTP_AUTH"  => filter_var(\App\Library\Env::get('MAIL_SMTP_AUTH', true), FILTER_VALIDATE_BOOLEAN),
        "MAIL_USER"       => \App\Library\Env::get('MAIL_USER', ''),
        "MAIL_SENHA"      => \App\Library\Env::get('MAIL_SENHA', ''),
        "MAIL_NOME"       => \App\Library\Env::get('MAIL_NOME', 'Projeto GP'),
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
