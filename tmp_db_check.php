<?php
require_once __DIR__ . '/app/config/config.php';
try {
    $c = DB_CONF_CONEXAO;
    $pdo = new PDO($c['DB_DRIVE'] . ":host=" . $c['DB_HOST'] . ";port=" . $c['DB_PORT'] . ";dbname=" . $c['DB_BDADOS'], $c['DB_USER'], $c['DB_PSW']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query('SELECT id, usuario_id, status, excluido_em FROM contatos ORDER BY id DESC LIMIT 20');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo $r['id'] . ' | ' . $r['usuario_id'] . ' | ' . $r['status'] . ' | ' . $r['excluido_em'] . PHP_EOL;
    }
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}
