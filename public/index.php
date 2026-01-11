<?php
    
session_start();

date_default_timezone_set("America/Sao_Paulo");

require __DIR__ . "app/config/config.php";

require __DIR__ . "vendor/autoload.php";

//Obtenção da rota (controller e o método)]
