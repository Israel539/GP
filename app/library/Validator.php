<?php

namespace App\Library;

/**
 * Validador de formulário simples, baseado em regras declarativas.
 *
 * Uso (dentro de um Model, em $validationRules):
 *  [
 *      'email' => ['rules' => 'required|email',    'label' => 'E-mail'],
 *      'nome'  => ['rules' => 'required|min:3',     'label' => 'Nome'],
 *  ]
 *
 * Validator::make($_POST, $model->validationRules) retorna TRUE se HOUVE erro
 * (e guarda os erros na sessão, em 'formErrors' + 'formInputs' para repopular
 * o formulário) ou FALSE se os dados estão válidos.
 */
class Validator
{
    public static function make(array $data, array $rules): bool
    {
        $errors = [];

        foreach ($rules as $campo => $regra) {
            $itensRule = explode("|", $regra['rules']);
            $valor     = $data[$campo] ?? null;

            foreach ($itensRule as $itemRule) {
                $partes = explode(":", $itemRule);
                $tipo   = $partes[0];

                switch ($tipo) {
                    case 'required':
                        if ($valor === null || $valor === '') {
                            $errors[$campo] = "O campo <b>{$regra['label']}</b> deve ser preenchido.";
                        }
                        break;

                    case 'email':
                        if ($valor !== null && $valor !== '' && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                            $errors[$campo] = "O campo <b>{$regra['label']}</b> não é um e-mail válido.";
                        }
                        break;

                    case 'float':
                        if ($valor !== null && $valor !== '' && filter_var($valor, FILTER_VALIDATE_FLOAT) === false) {
                            $errors[$campo] = "O campo <b>{$regra['label']}</b> deve conter um número decimal.";
                        }
                        break;

                    case 'int':
                        if ($valor !== null && $valor !== '' && filter_var($valor, FILTER_VALIDATE_INT) === false) {
                            $errors[$campo] = "O campo <b>{$regra['label']}</b> deve conter um número inteiro.";
                        }
                        break;

                    case 'min':
                        if ($valor !== null && strlen(strip_tags((string) $valor)) < (int) $partes[1]) {
                            $errors[$campo] = "O campo <b>{$regra['label']}</b> deve ter no mínimo {$partes[1]} caracteres.";
                        }
                        break;

                    case 'max':
                        if ($valor !== null && strlen(strip_tags((string) $valor)) > (int) $partes[1]) {
                            $errors[$campo] = "O campo <b>{$regra['label']}</b> deve ter no máximo {$partes[1]} caracteres.";
                        }
                        break;

                    case 'date':
                        if ($valor !== null && $valor !== '' && !strtotime((string) $valor)) {
                            $errors[$campo] = "O campo <b>{$regra['label']}</b> deve conter uma data válida.";
                        }
                        break;
                }
            }
        }

        if (!empty($errors)) {
            Session::set('formErrors', $errors);
            Session::set('formInputs', $data);
            return true;
        }

        Session::destroy('formErrors');
        Session::destroy('formInputs');
        return false;
    }
}
