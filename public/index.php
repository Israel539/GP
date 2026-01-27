<?php

date_default_timezone_set("America/Sao_Paulo");

// utilizando .. para subir um nivel e acesar a pasta app
require_once __DIR__ . "/../app/config/config.php";

require_once __DIR__ . "/../vendor/autoload.php";

//Obtenção da rota (controller e o método)

/**
 * $userUri => definição usuário que vai pesquisar na url.
 * 
 * @author israel
 * @param string $userUri
 */

// Obtenção da rota limpa

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($requestUri, '/');
$userUri = explode('/', $path);

// explode("/" -> divide a string em partes utilizando "slash ou tambem conhecida como  /" como separador..
// Determina qual controller deve ser chamado com base na URL passada

if (empty($userUri[0])) { // if usuario uri no indice 1 for vazio o controller padrão será o home.
    $controllerName = "Home";
} else {
    $controllerName = ucfirst($userUri[0]); //ucfirst -> coloca a primeira letra de uma String em maiúscula.    
}
//Decide qual fução dentro de qual classe erá disparada com no que veio na URL.
// Coração da execução.

// isset -> Determine se uma variável foi declarada e é diferente de NULL.
// $uri[2] : "index" -> verifica se existe algum valor na segunda posição, se não existir vai redirecionar para a pagina index.
$metodo = isset($userUri[1]) && !empty($userUri[1]) ? $userUri[1] : "index"; 
//junta o caminho das pastas nomespace - com o nome do controller que foi identificado ao atras.
$fullControllerName = "App\\Controller\\" .$controllerName;

// Verificação se a classe e existente.
if (class_exists($fullControllerName)) {
    $objController = new $fullControllerName();

    if (method_exists($objController,$metodo)) {
        $objController->$metodo();
    } else {
        http_response_code(404 );
        echo "Método (" .$metodo . ") não localizado no controller: " . $controllerName;
        }
        } else {
            http_response_code(404 );
            echo "Controller (" .$controllerName . ") não localizado: " . $fullControllerName;
}


// https://www.php-fig.org/
// https://httpd.apache.org/docs/current/rewrite/intro.html
