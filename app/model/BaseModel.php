<?php

namespace App\Model;

use App\Library\Database;

class BaseModel
{
    protected $connDb;

    public function __construct()
    {
        $this->connDb = new Database();
    }
}
