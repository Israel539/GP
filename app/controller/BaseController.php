<?php

namespace App\Controller;

use App\Library\Request;

class BaseController
{
    public $request;

    public function __construct()
    {
        $this->request = new Request();
        // Helpers já são carregados via composer.json 'files'
    }

    /**
     * model
     *
     * @param string $nomeModel 
     * @return object
     */
    public function model(string $nomeModel) : object
    {
        $nomeModel .= "Model";
        $fullClassName = "App\\Model\\" . $nomeModel;

        if (class_exists($fullClassName)) {
            return new $fullClassName();
        } else {
            return (object)null;
        }
    }

    /**
     * helper
     *
     * @param string|array $nomeHelper 
     * @return void
     */
    public function helper(string|array $nomeHelper)
    {
        // Mantido para compatibilidade, mas o Composer já carrega os helpers principais
        if (gettype($nomeHelper) == "string") {
            $nomeHelper = [$nomeHelper];
        }

        foreach ($nomeHelper as $helper) {
            
            $path =  __DIR__ . "/../helper/" . $helper . ".php";
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    /**
     * view
     *
     * @param string $view 
     * @param array $data 
     * @return void
     */
    public function view(string $view, array $data = [])
    {
        $data['action'] =  $this->request->getAction();
        
        $viewPath = __DIR__ . "/../view/" . $view . ".php";
        $errorPath = __DIR__ . "/../view/comuns/erros.php";

        if (file_exists($viewPath)){
            extract($data);
            require_once $viewPath;
        } else {
            // redireciona para erros.pp se a view não exisstir
            if (file_exists($errorPath)) {
                require_once $errorPath;
            } else {
                echo "Erro critico: A view solicitada ('$view) e a página de erro não foram encontradas.";
            }
        }
    }
}