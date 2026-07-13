<?php

namespace App\Library;

class Request
{
    protected $uri;

    public function __construct()
    {
        // parse_url + PHP_URL_PATH remove a query string (?x=1) da uri --
        // sem isso, um link como /Tarefa/update/insert/5?ref=kanban fazia
        // getId() devolver "5?ref=kanban" em vez de "5".
        $path      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->uri = explode("/", $path);
    }

    /**
     * getController
     *
     * @return string
     */
    public function getController(): string
    {
        return (isset($this->uri[1]) && $this->uri[1] !== "") ? ucfirst($this->uri[1]) : DEFAULT_CONTROLLER;
    }

    /**
     * getMetodo
     *
     * @return string
     */
    public function getMetodo(): string
    {
        return (isset($this->uri[2]) && $this->uri[2] !== "") ? $this->uri[2] : DEFAULT_METHOD;
    }

    /**
     * getAction
     *
     * @return string
     */
    public function getAction()
    {
        return (isset($this->uri[3]) ? $this->uri[3] : "");
    }

    /**
     * getId
     *
     * @return int
     */
    public function getId()
    {
        return (isset($this->uri[4]) ? $this->uri[4] : 0);
    }

    /**
     * getHttpMethod
     * Retorna o método HTTP real da requisição (GET, POST, PUT, PATCH, DELETE).
     *
     * @return string
     */
    public function getHttpMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * getPost
     * Retorna o $_POST já sem o token CSRF, com trim recursivo.
     *
     * @return array<string, mixed>
     */
    public function getPost(): array
    {
        $post = $_POST;

        if (defined('CSRF_TOKEN_NAME') && isset($post[CSRF_TOKEN_NAME])) {
            unset($post[CSRF_TOKEN_NAME]);
        }

        return $this->trimRecursive($post);
    }

    /**
     * getGet
     *
     * @return array<string, mixed>
     */
    public function getGet(): array
    {
        return $this->trimRecursive($_GET);
    }

    /**
     * trimRecursive
     *
     * @param array $data
     * @return array
     */
    private function trimRecursive(array $data): array
    {
        return array_map(function ($value) {
            if (is_array($value)) {
                return $this->trimRecursive($value);
            }
            return is_string($value) ? trim($value) : $value;
        }, $data);
    }
}
