<?php

namespace App\Library;

/**
 * DataComemorativaService
 * Datas comemorativas brasileiras (Dia das Maes, Namorados, etc.) -- NAO sao
 * feriado oficial (ninguem folga por causa delas), entao nao vem na
 * BrasilAPI (ver FeriadoService). Como sao poucas e a maioria e previsivel
 * (fixa, ou "enesimo domingo do mes"), mantem local em vez de depender de
 * mais uma API externa sujeita a cair/mudar/cobrar.
 */
class DataComemorativaService
{
    /**
     * doAno
     * Devolve as datas comemorativas do ano no formato
     * ['YYYY-MM-DD' => 'Nome da data'].
     *
     * @param int $ano
     * @return array<string, string>
     */
    public static function doAno(int $ano): array
    {
        $datas = [
            sprintf('%04d-03-08', $ano) => 'Dia Internacional da Mulher',
            sprintf('%04d-06-12', $ano) => 'Dia dos Namorados',
            sprintf('%04d-07-20', $ano) => 'Dia do Amigo',
            sprintf('%04d-08-11', $ano) => 'Dia do Estudante',
            sprintf('%04d-09-21', $ano) => 'Dia da Arvore',
            sprintf('%04d-10-15', $ano) => 'Dia do Professor',
            sprintf('%04d-10-31', $ano) => 'Halloween',
            sprintf('%04d-11-28', $ano) => 'Dia do Servidor Publico',
            sprintf('%04d-12-24', $ano) => 'Vespera de Natal',
            sprintf('%04d-12-31', $ano) => 'Vespera de Ano Novo',
        ];

        // Dia das Maes: 2o domingo de maio.
        $diaDasMaes = self::enesimoDiaDaSemana($ano, 5, 0, 2);
        $datas[$diaDasMaes] = 'Dia das Maes';

        // Dia dos Pais: 2o domingo de agosto.
        $diaDosPais = self::enesimoDiaDaSemana($ano, 8, 0, 2);
        $datas[$diaDosPais] = 'Dia dos Pais';

        // Dia das Criancas costuma coincidir com o feriado de Nsa. Sra.
        // Aparecida (12/10) -- nao repete aqui pra nao duplicar visualmente
        // no calendario, ja que o feriado nacional ja marca esse dia.

        ksort($datas);

        return $datas;
    }

    /**
     * enesimoDiaDaSemana
     * Acha a data do N-esimo dia-da-semana de um mes, ex: "2o domingo de
     * maio". $diaSemana segue o padrao do PHP: 0 = domingo ... 6 = sabado.
     *
     * @param int $ano
     * @param int $mes 1-12
     * @param int $diaSemana 0 (domingo) a 6 (sabado)
     * @param int $enesimo 1 = primeiro, 2 = segundo, etc.
     * @return string 'YYYY-MM-DD'
     */
    private static function enesimoDiaDaSemana(int $ano, int $mes, int $diaSemana, int $enesimo): string
    {
        $data = new \DateTime(sprintf('%04d-%02d-01', $ano, $mes));

        $diaSemanaPrimeiro = (int) $data->format('w');
        $diff = ($diaSemana - $diaSemanaPrimeiro + 7) % 7;

        $data->modify('+' . $diff . ' days');
        $data->modify('+' . (($enesimo - 1) * 7) . ' days');

        return $data->format('Y-m-d');
    }
}
