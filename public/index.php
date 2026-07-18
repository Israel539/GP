<?php

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
