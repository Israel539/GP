<?php


/**
 * baseUrl
 * 
 *
 * @return string
 */
function baseUrl()
{
    return BASEURL;
}

/**
 * diaSemana_pt
 * Converte o numero ISO-8601 do dia da semana (1=Segunda ... 7=Domingo,
 * retornado por date('N')) para o nome por extenso em portugues.
 *
 * @param int $numeroIso
 * @return string
 */
function diaSemana_pt(int $numeroIso): string
{
    $dias = [
        1 => 'segunda-feira', 2 => 'terça-feira', 3 => 'quarta-feira',
        4 => 'quinta-feira', 5 => 'sexta-feira', 6 => 'sábado', 7 => 'domingo',
    ];

    return $dias[$numeroIso] ?? '';
}

/**
 * mes_pt
 * Converte o numero do mes (1-12, retornado por date('n')) para o nome por
 * extenso em portugues.
 *
 * @param int $numeroMes
 * @return string
 */
function mes_pt(int $numeroMes): string
{
    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
    ];

    return $meses[$numeroMes] ?? '';
}

/**
 * strFloat
 *
 * @param string $valor 
 * @return float
 */
function strFloat(string $valor) : float
{
    return (float)str_replace(",", ".", str_replace(".", "", $valor));
}

/**
 * formatoValor
 *
 * @param float $valor 
 * @param int $decimais 
 * @return string
 */
function formatoValor(float $valor, int $decimais = 2) : string
{
    return number_format($valor, $decimais, ",", ".");
}
