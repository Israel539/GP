<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/config/config.php';

use App\Model\CompromissoRecorrenteModel;
use App\Model\CompromissoModel;

$recorrenteModel  = new CompromissoRecorrenteModel();
$compromissoModel = new CompromissoModel();

$resultados = $recorrenteModel->gerarPendentes($compromissoModel);

echo "[" . date('Y-m-d H:i:s') . "] Ocorrencias processadas: " . count($resultados) . "\n";

foreach ($resultados as $r) {
    $rotulo = $r['status'] === 'ok' ? 'OK' : 'PULADO';
    echo "  [{$rotulo}] \"{$r['titulo']}\" em {$r['data']} -- {$r['mensagem']}\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Varredura concluida.\n";
