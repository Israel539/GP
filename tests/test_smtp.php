<?php
// Script de diagnostico isolado do envio de e-mail, sem precisar passar por
// cadastro/login/convite. Roda direto contra o SMTP configurado em
// app/config/config.php e traduz o erro mais comum em portugues.
//
// Rodar a partir da raiz do projeto com: php tests/test_smtp.php seu@email-de-teste.com

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/config/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$destinatario = $argv[1] ?? null;

if (!$destinatario || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
    echo "Uso: php tests/test_smtp.php destinatario@email.com\n";
    echo "(informe um e-mail seu para receber o teste)\n";
    exit(1);
}

$conf = MAIL_CONF;

echo "========================================\n";
echo "Diagnostico de SMTP -- Projeto GP\n";
echo "========================================\n";
echo "Host:      {$conf['MAIL_HOST']}\n";
echo "Porta:     {$conf['MAIL_PORT']}\n";
echo "Seguranca: {$conf['MAIL_SMTPSECURE']}\n";
echo "Usuario:   {$conf['MAIL_USER']}\n";
echo "Senha:     " . str_repeat('*', strlen($conf['MAIL_SENHA'])) . " (" . strlen($conf['MAIL_SENHA']) . " caracteres)\n";
echo "Destino:   {$destinatario}\n";
echo "----------------------------------------\n\n";

if (empty($conf['MAIL_USER']) || empty($conf['MAIL_SENHA'])) {
    echo "[ERRO] MAIL_USER ou MAIL_SENHA estao vazios em app/config/config.php.\n";
    echo "Preencha os dois antes de testar.\n";
    exit(1);
}

if (strlen($conf['MAIL_SENHA']) !== 16) {
    echo "[AVISO] A Senha de App do Google normalmente tem exatamente 16 caracteres\n";
    echo "        (sem espacos). A sua tem " . strlen($conf['MAIL_SENHA']) . ". Se voce colou com\n";
    echo "        espacos (\"abcd efgh ijkl mnop\"), remova os espacos e tente de novo.\n\n";
}

if (!extension_loaded('openssl')) {
    echo "[ERRO] A extensao openssl do PHP nao esta habilitada.\n";
    echo "       No XAMPP: abra php.ini, procure por ';extension=openssl' e remova o ';'.\n";
    echo "       Depois reinicie o Apache.\n";
    exit(1);
}

$mail = new PHPMailer(true);
$logSmtp = '';
$mail->SMTPDebug   = SMTP::DEBUG_CONNECTION;
$mail->Debugoutput = function ($str) use (&$logSmtp) {
    $logSmtp .= $str . "\n";
};

try {
    $mail->isSMTP();
    $mail->Host       = $conf['MAIL_HOST'];
    $mail->SMTPAuth   = (bool) $conf['MAIL_SMTP_AUTH'];
    $mail->Username   = $conf['MAIL_USER'];
    $mail->Password   = $conf['MAIL_SENHA'];
    $mail->SMTPSecure = $conf['MAIL_SMTPSECURE'];
    $mail->Port       = (int) $conf['MAIL_PORT'];
    $mail->Timeout    = 10;

    $mail->setFrom($conf['MAIL_USER'], $conf['MAIL_NOME']);
    $mail->addAddress($destinatario);
    $mail->Subject = 'Teste de SMTP -- Projeto GP';
    $mail->Body    = 'Se voce recebeu isso, o SMTP esta configurado corretamente.';

    echo "Tentando enviar...\n\n";
    $mail->send();

    echo "[OK] E-mail enviado com sucesso! Confira a caixa de entrada (e o spam) de {$destinatario}.\n";
    exit(0);

} catch (\Exception $ex) {
    echo "[FALHOU] " . $mail->ErrorInfo . "\n\n";
    echo "--- log bruto da conversa com o servidor SMTP ---\n";
    echo $logSmtp . "\n";
    echo "--------------------------------------------------\n\n";

    $erroLimpo = strtolower($mail->ErrorInfo);
    $logCompleto = strtolower($logSmtp);

    echo "Diagnostico provavel:\n";

    // Checagem por ordem de especificidade -- autenticacao recusada e o sinal
    // mais definitivo (o servidor respondeu, entao NAO e problema de conexao/
    // firewall). Isso vem antes da checagem de timeout de proposito: o log de
    // debug do PHPMailer sempre imprime "timeout=10" como PARAMETRO de conexao
    // (Connection: opening to ...timeout=10...), mesmo quando a conexao deu
    // certo -- checar so a palavra "timeout" no log inteiro dava falso positivo.
    if (str_contains($erroLimpo, 'authenticate') || str_contains($logCompleto, '535') || str_contains($logCompleto, 'username and password not accepted') || str_contains($logCompleto, 'badcredentials')) {
        echo "-> Conectou no servidor, mas o GOOGLE RECUSOU usuario/senha (erro 535).\n";
        echo "   Confira, nesta ordem de probabilidade:\n";
        echo "   1. A Senha de App pode ter sido digitada/colada errada -- gere uma NOVA\n";
        echo "      em myaccount.google.com > Seguranca > Senhas de app e cole de novo,\n";
        echo "      sem espacos, com atencao a maiusculas/minusculas\n";
        echo "   2. Confirme que essa e uma Senha de App (16 caracteres) e NAO a senha\n";
        echo "      normal da conta Google -- a senha normal e sempre recusada aqui\n";
        echo "   3. Confirme que a verificacao em duas etapas esta ATIVADA nessa conta\n";
        echo "      (sem 2FA ligado, a secao 'Senhas de app' nem aparece nas configuracoes,\n";
        echo "      entao uma senha antiga gerada antes pode ter sido revogada)\n";
        echo "   4. O Google pode ter bloqueado por 'atividade incomum' -- acesse\n";
        echo "      https://myaccount.google.com/notifications nessa conta e veja se tem\n";
        echo "      algum alerta de login bloqueado para liberar\n";
    } elseif (str_contains($logCompleto, 'connection failed') || str_contains($logCompleto, 'connection timed out') || str_contains($logCompleto, 'connection refused') || str_contains($erroLimpo, 'could not connect')) {
        echo "-> Nao conseguiu nem CONECTAR na porta {$conf['MAIL_PORT']} do {$conf['MAIL_HOST']}.\n";
        echo "   Isso e quase sempre firewall do Windows, antivirus, ou o provedor de\n";
        echo "   internet bloqueando saida SMTP. Tente:\n";
        echo "   1. Desativar temporariamente o firewall/antivirus e testar de novo\n";
        echo "   2. Testar em outra rede (ex: dados do celular via hotspot)\n";
        echo "   3. Trocar a porta para 465 com MAIL_SMTPSECURE = 'ssl' no config.php\n";
    } elseif (str_contains($erroLimpo, 'ssl') || str_contains($erroLimpo, 'tls') || str_contains($erroLimpo, 'certificate')) {
        echo "-> Problema no aperto de mao TLS/SSL.\n";
        echo "   Confira se 'extension=openssl' esta habilitada no php.ini do XAMPP\n";
        echo "   (sem o ';' na frente) e reinicie o Apache depois de mudar.\n";
    } else {
        echo "-> Nao caiu em nenhum padrao conhecido -- copia o log bruto acima\n";
        echo "   e me manda que eu analiso.\n";
    }

    exit(1);
}
