<?php

namespace App\Model;

use App\Library\Database;
use App\Library\Validator;

class BaseModel
{
    protected $connDb;

    /**
     * validationRules
     * Cada Model filho define suas proprias regras, ex:
     *  protected $validationRules = [
     *      'titulo' => ['rules' => 'required|max:150', 'label' => 'Titulo'],
     *  ];
     *
     * @var array
     */
    protected $validationRules = [];

    public function __construct()
    {
        $this->connDb = new Database();
    }

    /**
     * validate
     * Roda o Validator em cima de $dados usando as $validationRules do Model.
     * Retorna TRUE se os dados sao validos, FALSE se ha erro (e o erro fica
     * disponivel na sessao, em 'formErrors').
     *
     * @param array $dados
     * @return bool
     */
    public function validate(array $dados): bool
    {
        $temErro = Validator::make($dados, $this->validationRules);
        return !$temErro;
    }
}
