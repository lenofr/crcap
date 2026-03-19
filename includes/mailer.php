<?php
/**
 * CRCAP Mailer Service
 * Sends emails using SMTP settings stored in DB.
 * Uses PHP's built-in socket connection (no external library needed).
 */

class CrcapMailer {
    private PDO $pdo;
    private array $config = [];
    private array $errors  = [];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->loadConfig();
    }

    // ── Load active SMTP config from DB ─────────────────────────────────────
    private function loadConfig(): void {
        $cfg = null;
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM smtp_settings WHERE is_active=1 ORDER BY is_default DESC LIMIT 1"
            );
            $stmt->execute();
            $cfg = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {}

        if ($cfg) {
            $cfg['password'] = base64_decode($cfg['password'] ?? '');
            $this->config    = $cfg;
        } else {
            // Fallback to PHP mail()
            $this->config = ['fallback' => true, 'from_email' => 'noreply@crcap.org.br', 'from_name' => 'CRCAP'];
        }
    }

    // ── Send a single email ──────────────────────────────────────────────────
    public function send(
        string $to,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody = '',
        string $fromEmail = '',
        string $fromName  = ''
    ): bool {
        $fromEmail = $fromEmail ?: ($this->config['from_email'] ?? 'noreply@crcap.org.br');
        $fromName  = $fromName  ?: ($this->config['from_name']  ?? 'CRCAP');
        $textBody  = $textBody  ?: strip_tags($htmlBody);

        // Fallback: use PHP mail()
        if (!empty($this->config['fallback'])) {
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=utf-8\r\n";
            $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromEmail>\r\n";
            $headers .= "X-Mailer: CRCAP Mailer\r\n";
            return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlBody, $headers);
        }

        // SMTP send
        try {
            return $this->smtpSend($to, $toName, $subject, $htmlBody, $textBody, $fromEmail, $fromName);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    // ── SMTP socket implementation ───────────────────────────────────────────
    private function smtpSend(
        string $to, string $toName,
        string $subject, string $html, string $text,
        string $from, string $fromName
    ): bool {
        $host       = $this->config['host']       ?? 'localhost';
        $port       = (int)($this->config['port']  ?? 587);
        $user       = $this->config['username']   ?? '';
        $pass       = $this->config['password']   ?? '';
        $encryption = $this->config['encryption'] ?? 'tls';

        $ctx    = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $prefix = ($encryption === 'ssl') ? 'ssl://' : '';
        $conn   = @stream_socket_client($prefix . $host . ':' . $port, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);

        if (!$conn) {
            throw new \Exception("Conexão SMTP falhou: $errstr ($errno)");
        }

        $read = function() use ($conn) {
            $data = '';
            while ($line = fgets($conn, 512)) {
                $data .= $line;
                if (substr($line, 3, 1) === ' ') break;
            }
            return $data;
        };

        $write = function(string $cmd) use ($conn) {
            fputs($conn, $cmd . "\r\n");
        };

        $read(); // Banner
        $write("EHLO " . gethostname());
        $read();

        // STARTTLS
        if ($encryption === 'tls') {
            $write("STARTTLS");
            $read();
            stream_socket_enable_crypto($conn, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
            $write("EHLO " . gethostname());
            $read();
        }

        // Auth
        if ($user && $pass) {
            $write("AUTH LOGIN");
            $read();
            $write(base64_encode($user));
            $read();
            $write(base64_encode($pass));
            $authResp = $read();
            if (strpos($authResp, '235') === false) {
                throw new \Exception("Falha na autenticação SMTP: $authResp");
            }
        }

        // MAIL FROM deve ser o e-mail autenticado no SMTP (evita erro 553)
        $mailFrom = !empty($user) ? $user : $from;
        $write("MAIL FROM: <$mailFrom>");
        $read();
        $write("RCPT TO: <$to>");
        $resp = $read();
        if (strpos($resp, '250') === false && strpos($resp, '251') === false) {
            throw new \Exception("RCPT TO rejeitado: $resp");
        }

        $write("DATA");
        $read();

        $boundary  = md5(uniqid('', true));
        $toDisplay = $toName ? "=?UTF-8?B?" . base64_encode($toName) . "?= <$to>" : "<$to>";
        // From header usa o e-mail autenticado no SMTP (evita rejeição 553)
        $smtpFrom     = !empty($user) ? $user : $from;
        $smtpFromName = $this->config['from_name'] ?? $fromName;
        $fromDisp  = $smtpFromName ? "=?UTF-8?B?" . base64_encode($smtpFromName) . "?= <$smtpFrom>" : "<$smtpFrom>";
        // Se o remetente da campanha for diferente, usa como Reply-To
        $replyDisp = ($from !== $smtpFrom && $from)
            ? ($fromName ? "=?UTF-8?B?" . base64_encode($fromName) . "?= <$from>" : "<$from>")
            : $fromDisp;
        $subjEnc   = "=?UTF-8?B?" . base64_encode($subject) . "?=";
        $msgId     = '<' . uniqid('', true) . '@crcap.org.br>';
        $date      = date('r');

        $msg = "Date: $date\r\n"
             . "To: $toDisplay\r\n"
             . "From: $fromDisp\r\n"
             . "Reply-To: $replyDisp\r\n"
             . "Subject: $subjEnc\r\n"
             . "Message-ID: $msgId\r\n"
             . "MIME-Version: 1.0\r\n"
             . "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n"
             . "X-Mailer: CRCAP Mailer 1.0\r\n"
             . "\r\n"
             . "--$boundary\r\n"
             . "Content-Type: text/plain; charset=utf-8\r\n"
             . "Content-Transfer-Encoding: base64\r\n"
             . "\r\n"
             . chunk_split(base64_encode($text)) . "\r\n"
             . "--$boundary\r\n"
             . "Content-Type: text/html; charset=utf-8\r\n"
             . "Content-Transfer-Encoding: base64\r\n"
             . "\r\n"
             . chunk_split(base64_encode($html)) . "\r\n"
             . "--$boundary--\r\n"
             . ".";

        $write($msg);
        $dataResp = $read();

        $write("QUIT");
        fclose($conn);

        if (strpos($dataResp, '250') === false) {
            throw new \Exception("Mensagem rejeitada: $dataResp");
        }

        // Update daily counter
        try {
            $this->pdo->prepare(
                "UPDATE smtp_settings SET emails_sent_today=emails_sent_today+1, last_reset_date=CURDATE()
                 WHERE id=? AND (last_reset_date IS NULL OR last_reset_date < CURDATE())"
            )->execute([$this->config['id'] ?? 0]);
            $this->pdo->prepare(
                "UPDATE smtp_settings SET emails_sent_today=emails_sent_today+1
                 WHERE id=? AND last_reset_date=CURDATE()"
            )->execute([$this->config['id'] ?? 0]);
        } catch (\Exception $e) {}

        return true;
    }

    // ── Test connection ──────────────────────────────────────────────────────
    public function testConnection(): array {
        if (!empty($this->config['fallback'])) {
            return ['success' => true, 'message' => 'Usando PHP mail() (fallback). Configure SMTP para melhor entregabilidade.'];
        }

        $host       = $this->config['host']       ?? '';
        $port       = (int)($this->config['port']  ?? 587);
        $encryption = $this->config['encryption'] ?? 'tls';
        $prefix     = ($encryption === 'ssl') ? 'ssl://' : '';
        $ctx        = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);

        $conn = @stream_socket_client($prefix . $host . ':' . $port, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);

        if (!$conn) {
            return ['success' => false, 'message' => "Não foi possível conectar a $host:$port — $errstr"];
        }

        $banner = fgets($conn, 512);
        fclose($conn);

        return ['success' => true, 'message' => "Conexão OK com $host:$port. Resposta: " . trim($banner)];
    }

    // ── Helper: send contact auto-reply ──────────────────────────────────────
    public function sendContactReply(string $name, string $email, string $subject): bool {
        $html = $this->wrapTemplate(
            "Olá, <strong>" . htmlspecialchars($name) . "</strong>!",
            "<p>Recebemos sua mensagem com o assunto: <em>" . htmlspecialchars($subject) . "</em>.</p>
             <p>Nossa equipe analisará sua solicitação e retornará em breve.</p>
             <p>Número de protocolo: <strong>#" . strtoupper(substr(md5($email . time()), 0, 8)) . "</strong></p>",
            "Acesse o Portal CRCAP", "https://crcap.org.br"
        );
        return $this->send($email, $name, "Recebemos sua mensagem – CRCAP", $html);
    }

    // ── Helper: send event confirmation ──────────────────────────────────────
    public function sendEventConfirmation(string $name, string $email, string $eventTitle, string $eventDate, string $confirmCode): bool {
        $html = $this->wrapTemplate(
            "Inscrição confirmada!",
            "<p>Olá, <strong>" . htmlspecialchars($name) . "</strong>!</p>
             <p>Sua inscrição no evento <strong>" . htmlspecialchars($eventTitle) . "</strong> foi confirmada.</p>
             <table style='margin:16px 0;border-collapse:collapse;width:100%'>
               <tr><td style='padding:8px;border:1px solid #e2e8f0;color:#001644;font-weight:bold'>Data:</td><td style='padding:8px;border:1px solid #e2e8f0'>" . htmlspecialchars($eventDate) . "</td></tr>
               <tr><td style='padding:8px;border:1px solid #e2e8f0;color:#001644;font-weight:bold'>Código:</td><td style='padding:8px;border:1px solid #e2e8f0'><strong>" . htmlspecialchars($confirmCode) . "</strong></td></tr>
             </table>
             <p>Guarde este e-mail como comprovante de inscrição.</p>",
            "Ver Detalhes do Evento", "https://crcap.org.br/pages/eventos.php"
        );
        return $this->send($email, $name, "Confirmação de inscrição – " . $eventTitle, $html);
    }

    // ── HTML email template wrapper ───────────────────────────────────────────
    public function wrapTemplate(string $title, string $body, string $btnText = '', string $btnUrl = ''): string {
        $siteName = 'CRCAP – Conselho Regional';
        $btn = $btnText ? "<div style='text-align:center;margin:24px 0'><a href='$btnUrl' style='background:#001644;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:14px'>$btnText</a></div>" : '';
        return <<<HTML
<!DOCTYPE html><html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#F8FAFC;font-family:Inter,Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8FAFC">
<tr><td align="center" style="padding:32px 16px">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,22,68,.07)">
  <tr><td style="background:linear-gradient(135deg,#001644,#022E6B);padding:28px 32px">
    <table width="100%"><tr>
      <td><div style="width:40px;height:40px;background:rgba(255,255,255,.15);border-radius:10px;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:20px;color:#fff;margin-right:12px;vertical-align:middle">C</div><span style="color:#fff;font-size:18px;font-weight:700;vertical-align:middle">$siteName</span></td>
    </tr></table>
  </td></tr>
  <tr><td style="padding:32px">
    <h2 style="color:#001644;font-size:20px;margin:0 0 16px">$title</h2>
    <div style="color:#022E6B;font-size:14px;line-height:1.7">$body</div>
    $btn
  </td></tr>
  <tr><td style="background:#F8FAFC;padding:16px 32px;border-top:1px solid #e2e8f0">
    <p style="color:#94a3b8;font-size:11px;margin:0;text-align:center">© " . date('Y') . " $siteName · Todos os direitos reservados</p>
  </td></tr>
</table>
</td></tr></table></body></html>
HTML;
    }

    public function getErrors(): array { return $this->errors; }
    public function getConfig(): array { return $this->config; }
}

// ── Singleton helper ──────────────────────────────────────────────────────────
function mailer(): CrcapMailer {
    global $pdo, $_crcapMailer;
    if (!isset($_crcapMailer)) {
        $_crcapMailer = new CrcapMailer($pdo);
    }
    return $_crcapMailer;
}