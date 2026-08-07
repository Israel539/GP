<?php
// Script de cron do Financeiro -- conecta transacoes_recorrentes com o
// financeiro de verdade: quando o dia_mes de uma recorrencia ativa chega,
// cria a transacao correspondente na conta (origem = 'recorrente') e marca
// a recorrencia como gerada neste mes (pra nao duplicar se o cron rodar
// mais de uma vez no mesmo dia).
//
// Regra do "dia do mes" em meses mais curtos (RN documentada no proprio
// form): se dia_mes = 31 e o mes so tem 30 dias (ou 28/29 em fevereiro), a
// transacao e lancada no ULTIMO dia do mes, nao no mes seguinte.
//
// Agendar para rodar 1x por dia (de madrugada, por exemplo):
//
// Windows (Agendador de Tarefas):
//   Programa/script:  C:\wamp64\bin\php\php8.4.0\php.exe
//   Argumentos:        C:\Users\...\GP\scripts\gerar_transacoes_recorrentes.php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/config/config.php';

use App\Model\RecorrenciaModel;
use App\Model\TransacaoModel;

$recorrenciaModel = new RecorrenciaModel();
$transacaoModel   = new TransacaoModel();

$resultados = $recorrenciaModel->gerarPendentes($transacaoModel);

echo "[" . date('Y-m-d H:i:s') . "] Recorrencias processadas: " . count($resultados) . "\n";

foreach ($resultados as $r) {
    $rotulo = ['ok' => 'OK', 'falhou' => 'FALHOU', 'erro' => 'ERRO'][$r['status']];
    echo "  [{$rotulo}] Recorrencia #{$r['id']} \"{$r['descricao']}\" -- {$r['mensagem']}\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Varredura concluida.\n";
