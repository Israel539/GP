<?php

namespace App\Library;

class Request
{
    protected $uri;

    public function __construct()
    {
        $this->uri = explode("/", $_SERVER['REQUEST_URI']);
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
}
