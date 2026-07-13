<?php

namespace App\Library;

/**
 * Proteção CSRF via Synchronizer Token Pattern.
 * Gera um token, guarda na sessão, e valida em toda requisição que muda
 * estado (POST/PUT/PATCH/DELETE).
 */
class Csrf
{
    private const SESSION_KEY      = 'csrf_token';
    private const SESSION_TIME_KEY = 'csrf_token_time';

    /**
     * generate
     * Gera um novo token e guarda na sessão com timestamp.
     *
     * @return string
     */
    public static function generate(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::set(self::SESSION_KEY, $token);
        Session::set(self::SESSION_TIME_KEY, time());

        return $token;
    }

    /**
     * getToken
     * Retorna o token atual, gerando um novo se ausente ou expirado.
     *
     * @return string
     */
    public static function getToken(): string
    {
        $token = Session::get(self::SESSION_KEY);
        $time  = Session::get(self::SESSION_TIME_KEY);

        if (!$token || !$time || (time() - (int) $time) > (int) CSRF_EXPIRE) {
            return self::generate();
        }

        return $token;
    }

    /**
     * getHiddenField
     * Retorna o HTML pronto do campo hidden, para inserir dentro de <form>.
     *
     * @return string
     */
    public static function getHiddenField(): string
    {
        $name  = htmlspecialchars(CSRF_TOKEN_NAME, ENT_QUOTES, 'UTF-8');
        $value = htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="' . $name . '" value="' . $value . '">';
    }

    /**
     * validate
     * Compara em tempo constante (hash_equals) para evitar timing attack.
     *
     * @param string|null $token
     * @return bool
     */
    public static function validate(?string $token): bool
    {
        $stored     = Session::get(self::SESSION_KEY);
        $storedTime = Session::get(self::SESSION_TIME_KEY);

        $valido = $stored
            && $storedTime
            && (time() - (int) $storedTime) <= (int) CSRF_EXPIRE
            && $token !== null
            && $token !== ''
            && hash_equals($stored, $token);

        if ($valido && CSRF_REGENERATE) {
            self::generate();
        }

        return $valido;
    }

    /**
     * isExcluded
     * Verifica se a URI atual está na lista de exclusão CSRF_EXCLUDE_URIS
     * (ex: endpoints de API/webhook que não usam formulário com token).
     *
     * @return bool
     */
    public static function isExcluded(): bool
    {
        $excludes = defined('CSRF_EXCLUDE_URIS') ? CSRF_EXCLUDE_URIS : [];

        if (empty($excludes)) {
            return false;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';

        foreach ($excludes as $pattern) {
            if ($pattern !== '' && strpos($uri, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}
