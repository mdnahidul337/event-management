<?php
/**
 * SCC Simple SMTP Mailer
 * Sends email via SSL SMTP with AUTH LOGIN — no Composer needed.
 */
class SMTPMailer
{
    private string $host;
    private int    $port;
    private string $user;
    private string $pass;
    private string $fromEmail;
    private string $fromName;
    private string $secure; // 'ssl' | 'tls' | 'none'
    private        $socket = null;
    public  string $lastError = '';
    public  array  $log = [];

    public function __construct(array $cfg)
    {
        $this->host      = $cfg['host']       ?? '';
        $this->port      = (int)($cfg['port'] ?? 465);
        $this->user      = $cfg['user']       ?? '';
        $this->pass      = $cfg['pass']       ?? '';
        $this->fromEmail = $cfg['from_email'] ?? $cfg['user'] ?? '';
        $this->fromName  = $cfg['from_name']  ?? 'SCC Club';
        $this->secure    = strtolower($cfg['secure'] ?? 'ssl');
    }

    // ── Low-level helpers ───────────────────────────────────────────────────
    private function cmd(string $cmd = ''): string
    {
        if ($cmd !== '') {
            fwrite($this->socket, $cmd . "\r\n");
            $this->log[] = '> ' . (str_contains($cmd, 'AUTH') ? '> [AUTH hidden]' : $cmd);
        }
        $resp = '';
        while ($line = fgets($this->socket, 512)) {
            $resp .= $line;
            if ($line[3] === ' ') break; // last line of multi-line response
        }
        $this->log[] = '< ' . trim($resp);
        return $resp;
    }

    private function code(string $resp): int
    {
        return (int)substr($resp, 0, 3);
    }

    // ── Connect & authenticate ──────────────────────────────────────────────
    public function connect(): bool
    {
        $prefix  = ($this->secure === 'ssl') ? 'ssl://' : '';
        $timeout = 15;

        $this->socket = @stream_socket_client(
            $prefix . $this->host . ':' . $this->port,
            $errno, $errstr, $timeout,
            STREAM_CLIENT_CONNECT,
            stream_context_create(['ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
            ]])
        );

        if (!$this->socket) {
            $this->lastError = "Connection failed: $errstr ($errno)";
            return false;
        }
        stream_set_timeout($this->socket, $timeout);

        $greeting = $this->cmd(); // Read server greeting
        if ($this->code($greeting) !== 220) {
            $this->lastError = "Bad greeting: $greeting";
            return false;
        }

        // EHLO
        $resp = $this->cmd('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        if ($this->code($resp) !== 250) {
            $this->lastError = "EHLO failed: $resp";
            return false;
        }

        // STARTTLS upgrade if TLS mode
        if ($this->secure === 'tls') {
            $resp = $this->cmd('STARTTLS');
            if ($this->code($resp) !== 220) {
                $this->lastError = "STARTTLS failed: $resp";
                return false;
            }
            stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->cmd('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        }

        // AUTH LOGIN
        $resp = $this->cmd('AUTH LOGIN');
        if ($this->code($resp) !== 334) {
            $this->lastError = "AUTH LOGIN failed: $resp";
            return false;
        }
        $resp = $this->cmd(base64_encode($this->user));
        if ($this->code($resp) !== 334) {
            $this->lastError = "Username rejected: $resp";
            return false;
        }
        $resp = $this->cmd(base64_encode($this->pass));
        if ($this->code($resp) !== 235) {
            $this->lastError = "Authentication failed (check password): $resp";
            return false;
        }

        return true;
    }

    // ── Send one email ──────────────────────────────────────────────────────
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        if (!$this->socket && !$this->connect()) return false;

        // MAIL FROM
        $resp = $this->cmd("MAIL FROM:<{$this->fromEmail}>");
        if ($this->code($resp) !== 250) {
            $this->lastError = "MAIL FROM failed: $resp";
            return false;
        }

        // RCPT TO
        $resp = $this->cmd("RCPT TO:<{$toEmail}>");
        if ($this->code($resp) !== 250) {
            $this->lastError = "RCPT TO failed ($toEmail): $resp";
            return false;
        }

        // DATA
        $resp = $this->cmd('DATA');
        if ($this->code($resp) !== 354) {
            $this->lastError = "DATA failed: $resp";
            return false;
        }

        $fromEncoded = '=?UTF-8?B?' . base64_encode($this->fromName) . '?=';
        $toEncoded   = '=?UTF-8?B?' . base64_encode($toName) . '?=';
        $subjEncoded = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $boundary    = 'SCC_' . md5(uniqid());
        $date        = date('r');

        $headers  = "Date: $date\r\n";
        $headers .= "From: $fromEncoded <{$this->fromEmail}>\r\n";
        $headers .= "To: $toEncoded <{$toEmail}>\r\n";
        $headers .= "Subject: $subjEncoded\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
        $headers .= "X-Mailer: SCC-SMTPMailer/1.0\r\n";

        $plain = strip_tags(str_replace(['<br>','<br/>','<br />','</p>','</div>'], "\n", $htmlBody));

        $body  = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($plain)) . "\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $body .= "--$boundary--\r\n";

        fwrite($this->socket, $headers . "\r\n" . $body . "\r\n.\r\n");
        $resp = $this->cmd();
        if ($this->code($resp) !== 250) {
            $this->lastError = "Message rejected: $resp";
            return false;
        }

        return true;
    }

    // ── Send to multiple with merge tags ────────────────────────────────────
    public function sendBulk(array $recipients, string $subject, string $htmlTemplate): array
    {
        $results = ['sent' => 0, 'failed' => 0, 'errors' => []];

        if (!$this->connect()) {
            $results['errors'][] = $this->lastError;
            $results['failed'] = count($recipients);
            return $results;
        }

        foreach ($recipients as $u) {
            $tags = [
                '{{name}}'       => $u['name']        ?? '',
                '{{email}}'      => $u['email']        ?? '',
                '{{role}}'       => $u['role_name']    ?? '',
                '{{department}}' => $u['department']   ?? '',
                '{{session}}'    => $u['session']      ?? '',
                '{{blood_group}}'=> $u['blood_group']  ?? '',
            ];
            $personalSubject = strtr($subject, $tags);
            $personalBody    = strtr($htmlTemplate, $tags);

            if ($this->send($u['email'], $u['name'], $personalSubject, $personalBody)) {
                $results['sent']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $u['email'] . ': ' . $this->lastError;
            }
            // Small delay between sends to avoid rate limiting
            usleep(200000); // 0.2s
        }

        $this->quit();
        return $results;
    }

    public function quit(): void
    {
        if ($this->socket) {
            $this->cmd('QUIT');
            fclose($this->socket);
            $this->socket = null;
        }
    }
}
