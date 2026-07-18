<?php

namespace App\Library;

/**
 * Env
 * Loader minimo de arquivo .env -- sem depender de nenhuma biblioteca externa
 * (tipo vlucas/phpdotenv). So le KEY=VALUE, ignora linhas em branco e
 * comentarios (#), e tira aspas simples/duplas ao redor do valor se tiver.
 */
class Env
{
    private static bool $carregado = false;

    /**
     * carregar
     * Le o arquivo .env (se existir) e popula $_ENV / getenv(). So roda uma
     * vez por request, mesmo se chamado de novo.
     *
     * @param string $caminhoArquivo
     * @return void
     */
    public static function carregar(string $caminhoArquivo): void
    {
        if (self::$carregado) {
            return;
        }

        self::$carregado = true;

        if (!file_exists($caminhoArquivo)) {
            return; // sem .env -- config.php cai nos valores padrao (dev local)
        }

        $linhas = file($caminhoArquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($linhas as $linha) {
            $linha = trim($linha);

            if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
                continue;
            }

            [$chave, $valor] = explode('=', $linha, 2);
            $chave = trim($chave);
            $valor = trim($valor);

            if (strlen($valor) >= 2) {
                $primeiro = $valor[0];
                $ultimo = $valor[strlen($valor) - 1];
                if (($primeiro === '"' && $ultimo === '"') || ($primeiro === "'" && $ultimo === "'")) {
                    $valor = substr($valor, 1, -1);
                }
            }

            if (!array_key_exists($chave, $_ENV)) {
                $_ENV[$chave] = $valor;
                putenv("{$chave}={$valor}");
            }
        }
    }

    /**
     * get
     *
     * @param string $chave
     * @param mixed $padrao
     * @return mixed
     */
    public static function get(string $chave, $padrao = null)
    {
        if (array_key_exists($chave, $_ENV)) {
            return $_ENV[$chave];
        }

        $valor = getenv($chave);
        return $valor !== false ? $valor : $padrao;
    }
}
