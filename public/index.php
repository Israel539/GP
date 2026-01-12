<?php
    
session_start();

date_default_timezone_set("America/Sao_Paulo");

require __DIR__ . "app/config/config.php";

require __DIR__ . "vendor/autoload.php";

//Obtenção da rota (controller e o método)

/**
 * $userUri => definição usuário que vai pesquisar na url.
 * 
 * @author israel
 * @param string $userUri
 */

$userUri = explode("/", $_SERVER['REQUEST_URI']); // explode("/" -> divide a string em partes utilizando "slash ou tambem conhecida como  /" como separador..
// Determina qual controller deve ser chamado com base na URL passada
if ($_SERVER['REQUEST_URI'] == "/" || empty($userUri[1])) { // if usuario uri no indice 1 for vazio o controller padrão será o home.
    $controllerNane = "Home";
} else {
    $controllerNane = ucfirst($uri[1]); //ucfirst -> coloca a primeira letra de uma String em maiúscula.    
}



$metodo = isset($uri[2]) ? $uri[2] : "index"; // isset -> Determine se uma variável foi declarada e é diferente de NULL.



