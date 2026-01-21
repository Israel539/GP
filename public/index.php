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
    $controllerName = "Home";
} else {
    $controllerName = ucfirst($uri[1]); //ucfirst -> coloca a primeira letra de uma String em maiúscula.    
}
//Decide qual fução dentro de qual classe erá disparada com no que veio na URL.
// Coração da execução.

// isset -> Determine se uma variável foi declarada e é diferente de NULL.
// $uri[2] : "index" -> verifica se existe algum valor na segunda posição, se não existir vai redirecionar para a pagina index.
$metodo = isset($uri[2]) ? $uri[2] : "index"; 
//junta o caminho das pastas nomespace - com o nome do controller que foi identificado ao atras.
$fullControllerName = "App\\Controller\\" .$controllerName;

// Verificação se a classe e existente.
if (class_exists($fullControllerName)) {
    $objController = new $fullControllerName();

    if (method_exists($objController,$mmetodo)) {
        $objController->$metodo();
    } else {
        echo "Método (" .$metodo . ") não localizado no controller: " . $controllerName;
        }
        } else {
            echo "Controller (" .$controllerName . ") não localizado: " . $fullControllerName;
}


// https://www.php-fig.org/
// https://httpd.apache.org/docs/current/rewrite/intro.html
