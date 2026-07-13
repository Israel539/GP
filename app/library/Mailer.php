<?php

namespace App\Library;

/**
 * Mailer — envio de e-mails via SMTP, usando as configuracoes de MAIL_CONF
 * em app/config/config.php (adaptado do Mailer da Extensao/fasmicro, que
 * usava .env -- aqui o GP usa constantes fixas, igual o DB_CONF_CONEXAO).
 *
 * Usa PHPMailer se estiver instalado (composer require phpmailer/phpmailer,
 * ja configurado no composer.json). Se a biblioteca nao estiver presente,
 * cai para a funcao mail() nativa do PHP (funciona mal com Gmail, que exige
 * autenticacao SMTP, mas evita fatal error em ambiente sem composer install).
 */
class Mailer
{
    /**
     * enviar
     *
     * @param string $destinatarioEmail
     * @param string $destinatarioNome
     * @param string $assunto
     * @param string $corpoHtml
     * @param string|null $remetenteNomeExibicao Nome que aparece como remetente
     *        (ex: "Beto convidou voce"). A conta que efetivamente autentica e
     *        envia continua sendo sempre MAIL_USER -- so o nome de exibicao muda.
     *        Se null, usa o MAIL_NOME padrao ("Projeto GP").
     * @return array ['ok' => bool, 'erro' => string|null] -- 'erro' vem preenchido
     *         sempre que 'ok' for false, para o Controller poder mostrar (ou logar)
     *         o motivo real, em vez de assumir sucesso so porque nao ouve excecao.
     */
    public static function enviar(string $destinatarioEmail, string $destinatarioNome, string $assunto, string $corpoHtml, ?string $remetenteNomeExibicao = null): array
    {
        $conf = MAIL_CONF;

        if (!empty($remetenteNomeExibicao)) {
            $conf['MAIL_NOME'] = $remetenteNomeExibicao;
        }

        if (empty($conf['MAIL_USER'])) {
            // SMTP ainda nao configurado (MAIL_USER vazio em config.php) --
            // nao tenta enviar, so avisa no log, para nao travar quem esta
            // desenvolvendo sem ter configurado um e-mail de envio ainda.
            $erro = "SMTP nao configurado (MAIL_USER vazio em config.php).";
            error_log("Mailer: {$erro} E-mail para {$destinatarioEmail} NAO enviado. Assunto: {$assunto}");
            return ['ok' => false, 'erro' => $erro];
        }

        if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
            return self::enviarComPhpMailer($destinatarioEmail, $destinatarioNome, $assunto, $corpoHtml, $conf);
        }

        return self::enviarComMailNativo($destinatarioEmail, $destinatarioNome, $assunto, $corpoHtml, $conf);
    }

    /**
     * enviarComPhpMailer
     *
     * @param string $destinatarioEmail
     * @param string $destinatarioNome
     * @param string $assunto
     * @param string $corpoHtml
     * @param array $conf
     * @return array ['ok' => bool, 'erro' => string|null]
     */
    private static function enviarComPhpMailer(string $destinatarioEmail, string $destinatarioNome, string $assunto, string $corpoHtml, array $conf): array
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        // Guarda o log de debug do SMTP (a "conversa" bruta com o servidor
        // do Gmail) numa string -- se falhar, isso vai pro error_log e diz
        // EXATAMENTE em que ponto travou (autenticacao, conexao, TLS, etc.),
        // em vez de so "deu erro".
        $logSmtp = '';
        $mail->SMTPDebug   = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
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
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($conf['MAIL_USER'], $conf['MAIL_NOME']);
            $mail->addAddress($destinatarioEmail, $destinatarioNome);

            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body    = $corpoHtml;
            $mail->AltBody = strip_tags($corpoHtml);

            $mail->send();
            return ['ok' => true, 'erro' => null];
        } catch (\Exception $ex) {
            $erro = $mail->ErrorInfo ?: $ex->getMessage();
            error_log("Mailer: falha ao enviar via PHPMailer para {$destinatarioEmail} -- {$erro}\n--- log SMTP ---\n{$logSmtp}");
            return ['ok' => false, 'erro' => $erro];
        }
    }

    /**
     * enviarComMailNativo
     *
     * @param string $destinatarioEmail
     * @param string $destinatarioNome
     * @param string $assunto
     * @param string $corpoHtml
     * @param array $conf
     * @return array ['ok' => bool, 'erro' => string|null]
     */
    private static function enviarComMailNativo(string $destinatarioEmail, string $destinatarioNome, string $assunto, string $corpoHtml, array $conf): array
    {
        $headers  = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: {$conf['MAIL_NOME']} <{$conf['MAIL_USER']}>" . "\r\n";

        $enviado = @mail($destinatarioEmail, $assunto, $corpoHtml, $headers);

        if (!$enviado) {
            $erro = 'Falha na funcao mail() nativa do PHP (sem PHPMailer instalado). '
                  . 'No Windows/XAMPP isso quase sempre falha porque nao ha um servidor '
                  . 'de envio local configurado -- prefira o PHPMailer via SMTP.';
            error_log("Mailer: {$erro} Destinatario: {$destinatarioEmail}");
            return ['ok' => false, 'erro' => $erro];
        }

        return ['ok' => true, 'erro' => null];
    }
}
