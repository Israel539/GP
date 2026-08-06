<?php

namespace App\Library;

/**
 * FeriadoService
 * Busca feriados nacionais na BrasilAPI (https://brasilapi.com.br/api/feriados/v1/{ano})
 * e guarda em cache local (arquivo JSON por ano), porque feriado nao muda e nao
 * faz sentido bater na API externa a cada carregamento do calendario. Se a API
 * estiver fora do ar, usa o ultimo cache salvo -- e se nao tiver cache nenhum,
 * devolve vazio (o calendario continua funcionando normalmente, so sem os
 * feriados marcados).
 */
class FeriadoService
{
    private const URL_BASE  = 'https://brasilapi.com.br/api/feriados/v1/';
    private const CACHE_DIR = __DIR__ . '/../../storage/cache/feriados';
    private const CACHE_TTL_SEGUNDOS = 30 * 24 * 60 * 60; // 30 dias

    /**
     * doAno
     * Devolve os feriados do ano no formato ['YYYY-MM-DD' => 'Nome do feriado'].
     *
     * @param int $ano
     * @return array<string, string>
     */
    public static function doAno(int $ano): array
    {
        $arquivoCache = self::CACHE_DIR . "/{$ano}.json";
        $cacheValido  = file_exists($arquivoCache) && (time() - filemtime($arquivoCache)) < self::CACHE_TTL_SEGUNDOS;

        if ($cacheValido) {
            $doCache = self::lerCache($arquivoCache);
            if ($doCache !== null) {
                return $doCache;
            }
        }

        $daApi = self::buscarNaApi($ano);

        if ($daApi !== null) {
            self::salvarCache($arquivoCache, $daApi);
            return $daApi;
        }

        // API fora do ar / sem internet: usa cache antigo se existir, mesmo vencido.
        $doCache = self::lerCache($arquivoCache);
        return $doCache ?? [];
    }

    /**
     * doIntervaloDeAnos
     * Atalho pra pegar feriados de varios anos de uma vez (usado quando o
     * grid do calendario mostra dias "de fora" que caem em outro ano, ex:
     * ultima semana de dezembro mostrando os primeiros dias de janeiro).
     *
     * @param int[] $anos
     * @return array<string, string>
     */
    public static function doIntervaloDeAnos(array $anos): array
    {
        $feriados = [];
        foreach (array_unique($anos) as $ano) {
            $feriados += self::doAno((int) $ano);
        }
        return $feriados;
    }

    /**
     * buscarNaApi
     *
     * @param int $ano
     * @return array<string, string>|null null quando a chamada falha por qualquer motivo
     */
    private static function buscarNaApi(int $ano): ?array
    {
        if ($ano < 1900 || $ano > 2199 || !function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init(self::URL_BASE . $ano);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 4,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);

        // Windows/WAMP classicamente nao acha o bundle de certificados raiz
        // do sistema pra validar HTTPS (curl_errno 60). Em vez de depender
        // de configurar curl.cainfo no php.ini (que exige achar o php.ini
        // certo entre o do Apache e o do CLI, e mexer fora do projeto), se
        // existir um cacert.pem dentro do proprio projeto a gente usa ele.
        // Ver app/config/README-cacert.md pra instrucoes de como baixar.
        $cacertLocal = __DIR__ . '/../config/cacert.pem';
        if (file_exists($cacertLocal)) {
            curl_setopt($ch, CURLOPT_CAINFO, $cacertLocal);
        }

        $resposta   = curl_exec($ch);
        $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erro       = curl_error($ch);
        curl_close($ch);

        if ($erro !== '' || $codigoHttp !== 200 || $resposta === false) {
            return null;
        }

        $dados = json_decode($resposta, true);
        if (!is_array($dados)) {
            return null;
        }

        // A BrasilAPI devolve [{date, name, type}, ...] -- normaliza pra
        // ['YYYY-MM-DD' => 'Nome'], que e o formato que o resto do codigo usa.
        $porDia = [];
        foreach ($dados as $item) {
            if (isset($item['date'], $item['name'])) {
                $porDia[$item['date']] = $item['name'];
            }
        }

        return $porDia;
    }

    /**
     * lerCache
     *
     * @param string $arquivo
     * @return array<string, string>|null
     */
    private static function lerCache(string $arquivo): ?array
    {
        if (!file_exists($arquivo)) {
            return null;
        }

        $conteudo = file_get_contents($arquivo);
        $dados    = json_decode((string) $conteudo, true);

        return is_array($dados) ? $dados : null;
    }

    /**
     * salvarCache
     *
     * @param string $arquivo
     * @param array<string, string> $dados
     * @return void
     */
    private static function salvarCache(string $arquivo, array $dados): void
    {
        $pasta = dirname($arquivo);
        if (!is_dir($pasta)) {
            @mkdir($pasta, 0775, true);
        }

        @file_put_contents($arquivo, json_encode($dados));
    }
}
