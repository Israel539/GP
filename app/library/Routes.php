<?php

namespace App\Library;

class Routes
{
    /**
     * rota
     * Resolve controller/método a partir da URL e dispara a execução.
     * Centraliza o que antes estava direto no public/index.php.
     *
     * @return void
     */
    public static function rota()
    {
        $request = new Request();

        $controllerName     = $request->getController();
        $metodo             = $request->getMetodo();
        $fullControllerName = "App\\Controller\\" . $controllerName;

        if (!class_exists($fullControllerName)) {
            http_response_code(404);
            echo "Controller (" . $controllerName . ") não localizado: " . $fullControllerName;
            return;
        }

        $objController = new $fullControllerName();

        if (!method_exists($objController, $metodo)) {
            http_response_code(404);
            echo "Método (" . $metodo . ") não localizado no controller: " . $controllerName;
            return;
        }

        $objController->$metodo();
    }
}
