<?php
// Script de cron da Agenda Pessoal (item 7) -- apaga compromissos com
// status = 'concluido' cuja ultima atualizacao passou de 30 dias, mas SO
// para usuarios que ativaram a opcao "Exclusao automatica de concluidos"
// na tela da Agenda (usuarios.agenda_limpeza_automatica = 1, ver migracao
// 005). Quem nao ativou a opcao nao perde nenhum registro.
//
// Agendar para rodar 1x por dia (de madrugada, por exemplo):
// Windows (Agendador de Tarefas):
//   Programa/script:  C:\xampp\php\php.exe
//   Argumentos:        C:\Users\...\GP\scripts\limpar_compromissos_concluidos.php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/config/config.php';

use App\Model\CompromissoModel;

$compromissoModel = new CompromissoModel();

echo "[" . date('Y-m-d H:i:s') . "] Iniciando limpeza automatica de compromissos concluidos (item 7)...\n";

$diasRetencao = 30;
$totalExcluido = $compromissoModel->excluirConcluidosAntigos($diasRetencao);

echo "  {$totalExcluido} compromisso(s) concluido(s) ha mais de {$diasRetencao} dias foram excluidos"
    . " (apenas de usuarios com a opcao ativada).\n";

echo "[" . date('Y-m-d H:i:s') . "] Limpeza concluida.\n";
