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
