<?php

namespace App\Library;

/**
 * Wrapper estático em cima do $_SESSION nativo do PHP.
 * Mantém total compatibilidade com o código que já lê/grava $_SESSION
 * diretamente (ex: Login.php, helper/crud.php) -- é o MESMO array por baixo.
 */
class Session
{
    /**
     * set
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * get
     *
     * @param string $key
     * @return mixed
     */
    public static function get(string $key)
    {
        return $_SESSION[$key] ?? false;
    }

    /**
     * destroy
     *
     * @param string $key
     * @return void
     */
    public static function destroy(string $key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * getDestroy
     * Lê o valor e já apaga da sessão em seguida (útil para msgSucesso/msgError,
     * que devem aparecer uma única vez).
     *
     * @param string $key
     * @return mixed
     */
    public static function getDestroy(string $key)
    {
        $valor = self::get($key);
        self::destroy($key);

        return $valor;
    }
}
