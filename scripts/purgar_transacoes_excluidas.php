<?php
// Agendar para rodar algumas vezes ao dia (ex: a cada hora), ja que o prazo
// de restauracao e curto (1 dia) -- rodando so 1x/dia como os outros crons
// do projeto, uma transacao excluida logo depois da execucao ficaria quase
// 24h a mais na lixeira do que deveria antes de ser purgada.
//
// Windows (Agendador de Tarefas):
//   Programa/script:  C:\xampp\php\php.exe
//   Argumentos:        C:\Users\...\GP\scripts\purgar_transacoes_excluidas.php
//   Disparar:          A cada 1 hora

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/config/config.php';

use App\Model\TransacaoModel;

$transacaoModel = new TransacaoModel();

echo "[" . date('Y-m-d H:i:s') . "] Purgando transacoes na lixeira ha mais de 24h...\n";

$horasRetencao = 24;
$totalPurgado  = $transacaoModel->purgarExcluidasAntigas($horasRetencao);

echo "  {$totalPurgado} transacao(oes) excluida(s) definitivamente"
    . " (estavam na lixeira ha mais de {$horasRetencao}h).\n";

echo "[" . date('Y-m-d H:i:s') . "] Purga concluida.\n";
