<?php

// Forca o cookie de sessao a ser "de sessao mesmo" (expira quando o
// navegador fecha de verdade) -- independente do que o php.ini do XAMPP
// tiver configurado em session.cookie_lifetime. httponly evita que
// JavaScript malicioso (XSS) leia esse cookie.
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

date_default_timezone_set("America/Sao_Paulo");

require_once __DIR__ . "/../vendor/autoload.php";

// utilizando .. para subir um nivel e acesar a pasta app
require_once __DIR__ . "/../app/config/config.php";

// Todo o parsing de URL e despacho para o Controller/metodo agora fica
// centralizado em App\Library\Routes -- ver esse arquivo para entender o fluxo.
App\Library\Routes::rota();

// https://www.php-fig.org/
// https://httpd.apache.org/docs/current/rewrite/intro.html