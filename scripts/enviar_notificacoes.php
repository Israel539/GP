<?php
// Script de cron da Agenda Pessoal -- RN03: varre os compromissos pendentes
// que vencem nas proximas 24h e ainda nao foram notificados, manda o aviso,
// e so ENTAO marca a flag de envio (para nao mandar 2x -- e essa flag que
// evita spam, exigida pela RN03).
//
// Agendar para rodar 1x por hora (ou a cada 15min, se quiser mais precisao):
//
// Linux (crontab -e):
//   0 * * * * /usr/bin/php /caminho/para/GP/scripts/enviar_notificacoes.php
//
// Windows (Agendador de Tarefas):
//   Programa/script:  C:\xampp\php\php.exe
//   Argumentos:        C:\Users\...\GP\scripts\enviar_notificacoes.php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/config/config.php';

use App\Model\CompromissoModel;
use App\Library\Mailer;

$compromissoModel = new CompromissoModel();

echo "[" . date('Y-m-d H:i:s') . "] Iniciando varredura de notificacoes (RN03)...\n";

// ------------------------------------------------------------------
// Canal: E-MAIL
// ------------------------------------------------------------------
$paraNotificarEmail = $compromissoModel->listarParaNotificar('email', 24);
echo "E-mail: " . count($paraNotificarEmail) . " compromisso(s) elegivel(is).\n";

foreach ($paraNotificarEmail as $compromisso) {
    $corpoHtml = "
        <p>Ola, " . htmlspecialchars($compromisso['usuario_nome']) . "!</p>
        <p>Voce tem um compromisso chegando:</p>
        <p><strong>" . htmlspecialchars($compromisso['titulo']) . "</strong><br>
        " . date('d/m/Y H:i', strtotime($compromisso['data_inicio'])) . "</p>
        " . (!empty($compromisso['local']) ? '<p>Local: ' . htmlspecialchars($compromisso['local']) . '</p>' : '') . "
        <p>Acesse o Projeto GP para ver os detalhes.</p>
    ";

    $resultado = Mailer::enviar(
        $compromisso['usuario_email'],
        $compromisso['usuario_nome'],
        'Lembrete: ' . $compromisso['titulo'],
        $corpoHtml
    );

    if ($resultado['ok']) {
        // So marca como notificado DEPOIS de confirmar o envio -- se o
        // Mailer falhar (ex: SMTP fora do ar), o proximo ciclo do cron
        // tenta de novo, em vez de perder o aviso silenciosamente.
        $compromissoModel->marcarNotificado((int) $compromisso['id'], 'email');
        echo "  [OK] #{$compromisso['id']} -- {$compromisso['usuario_email']}\n";
    } else {
        echo "  [FALHOU] #{$compromisso['id']} -- {$compromisso['usuario_email']} -- {$resultado['erro']}\n";
    }
}

// ------------------------------------------------------------------
// Canal: WHATSAPP -- DESATIVADO
// ------------------------------------------------------------------
// Voltou a ser so e-mail por decisao do projeto. O codigo do gateway
// (app/Library/WhatsappGateway.php) continua no repositorio, so nao e mais
// chamado daqui -- se um dia quiser reativar, e so descomentar o bloco
// abaixo e o formulario de compromisso volta a mostrar o checkbox de novo.
//
// $paraNotificarWhatsapp = $compromissoModel->listarParaNotificar('whatsapp', 24);
// foreach ($paraNotificarWhatsapp as $compromisso) { ... }

echo "[" . date('Y-m-d H:i:s') . "] Varredura concluida.\n";
