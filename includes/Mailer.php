<?php

class Mailer
{
    private PDO $db;
    private array $config;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->config = $this->loadConfig();
    }

    public function isConfigured(): bool
    {
        return trim((string)($this->config['host'] ?? '')) !== ''
            && trim((string)($this->config['from_email'] ?: $this->config['username'])) !== '';
    }

    public function getAdminNotificationEmail(): string
    {
        $configured = trim((string)($this->config['admin_email'] ?? ''));
        if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_EMAIL)) {
            return $configured;
        }

        $stmt = $this->db->query("SELECT email FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
        $adminEmail = trim((string)$stmt->fetchColumn());
        if ($adminEmail !== '' && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            return $adminEmail;
        }

        $fromStore = trim((string)$this->getStoreSetting('email'));
        if ($fromStore !== '' && filter_var($fromStore, FILTER_VALIDATE_EMAIL)) {
            return $fromStore;
        }

        return trim((string)($this->config['from_email'] ?: $this->config['username']));
    }

    public function getStoreSetting(string $key, string $default = ''): string
    {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'storefront' LIMIT 1");
            $stmt->execute();
            $json = $stmt->fetchColumn();
            if ($json !== false) {
                $decoded = json_decode((string)$json, true);
                if (is_array($decoded) && isset($decoded[$key]) && trim((string)$decoded[$key]) !== '') {
                    return trim((string)$decoded[$key]);
                }
            }
            $stmt2 = $this->db->prepare('SELECT setting_value FROM store_settings WHERE setting_key = :k LIMIT 1');
            $stmt2->execute(['k' => $key]);
            $val = $stmt2->fetchColumn();
            if ($val !== false && trim((string)$val) !== '') {
                return trim((string)$val);
            }
        } catch (Throwable $e) {
            return $default;
        }
        return $default;
    }

    public function getBaseUrl(): string
    {
        $envUrl = trim((string)(getenv('APP_URL') ?: ''));
        if ($envUrl !== '') {
            return rtrim($envUrl, '/');
        }
        if (!empty($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            $dir = '';
            if (preg_match('#^(.*)/MVC/router\.php$#i', $script, $m)) {
                $dir = $m[1];
            } else {
                $dir = rtrim(dirname($script), '/');
            }
            return $scheme . '://' . $_SERVER['HTTP_HOST'] . $dir;
        }
        return 'http://localhost/vaststudio';
    }

    public function sendTemplated(array $payload): array
    {
        $recipient = trim((string)($payload['recipient_email'] ?? ''));
        $subject = trim((string)($payload['subject'] ?? ''));
        $templateCode = trim((string)($payload['template_code'] ?? 'notification'));
        $bodyHtml = (string)($payload['body_html'] ?? '');
        $bodyText = (string)($payload['body_text'] ?? strip_tags($bodyHtml));
        $userId = isset($payload['user_id']) && (int)$payload['user_id'] > 0 ? (int)$payload['user_id'] : null;
        $orderId = isset($payload['order_id']) && (int)$payload['order_id'] > 0 ? (int)$payload['order_id'] : null;
        $idempotencyKey = trim((string)($payload['idempotency_key'] ?? ''));

        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $err = 'Địa chỉ email người nhận không hợp lệ hoặc bị trống.';
            $logId = $this->recordLog($userId, $orderId, $recipient !== '' ? $recipient : 'missing@invalid', $subject, $templateCode, $bodyHtml, $bodyText, $idempotencyKey ?: null, 'failed', $err);
            return ['sent' => false, 'status' => 'failed', 'error' => $err, 'log_id' => $logId];
        }

        if ($idempotencyKey !== '') {
            $existing = $this->findByIdempotencyKey($idempotencyKey);
            if ($existing) {
                if ($existing['status'] === 'sent') {
                    return ['sent' => true, 'status' => 'skipped_duplicate', 'log_id' => (int)$existing['id']];
                }
                return $this->retry((int)$existing['id']);
            }
        }

        $logId = $this->recordLog($userId, $orderId, $recipient, $subject, $templateCode, $bodyHtml, $bodyText, $idempotencyKey ?: null, 'pending', null);
        return $this->attemptSendAndLog($logId, $recipient, $subject, $bodyHtml, $bodyText, 1);
    }

    public function retry(int $emailLogId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM email_logs WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $emailLogId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException('Không tìm thấy bản ghi email cần gửi lại.');
        }
        $attempts = ((int)($row['attempts'] ?? 1)) + 1;
        return $this->attemptSendAndLog(
            (int)$row['id'],
            (string)$row['recipient_email'],
            (string)$row['subject'],
            (string)($row['body_html'] ?? ''),
            (string)($row['body_text'] ?? ''),
            $attempts
        );
    }

    public static function wrapHtml(string $title, string $contentHtml, string $ctaUrl = '', string $ctaText = ''): string
    {
        $ctaBlock = '';
        if ($ctaUrl !== '' && $ctaText !== '') {
            $safeUrl = htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8');
            $safeText = htmlspecialchars($ctaText, ENT_QUOTES, 'UTF-8');
            $ctaBlock = "<p style='margin:24px 0 8px;'><a href='{$safeUrl}' style='display:inline-block;padding:12px 22px;background:#09090b;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:700;font-size:14px;'>{$safeText}</a></p>";
        }
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        return "<!DOCTYPE html><html lang='vi'><body style='margin:0;padding:24px;background:#f3f5f8;font-family:Segoe UI,Arial,sans-serif;color:#09090b;'>
            <div style='max-width:620px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;'>
                <div style='background:#09090b;color:#ffffff;padding:20px 24px;'>
                    <div style='font-size:12px;letter-spacing:2px;color:#94a3b8;text-transform:uppercase;font-weight:700;'>VAST STUDIO</div>
                    <h1 style='margin:6px 0 0;font-size:20px;font-weight:700;'>{$safeTitle}</h1>
                </div>
                <div style='padding:24px;font-size:14px;line-height:1.6;color:#1e293b;'>
                    {$contentHtml}
                    {$ctaBlock}
                </div>
                <div style='padding:16px 24px;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:12px;color:#64748b;'>
                    Email tự động từ hệ thống VAST STUDIO. Vui lòng giữ lại thông tin này để đối chiếu khi cần hỗ trợ.
                </div>
            </div>
        </body></html>";
    }

    private function attemptSendAndLog(int $logId, string $recipient, string $subject, string $bodyHtml, string $bodyText, int $attempts): array
    {
        try {
            if (!$this->isConfigured()) {
                throw new RuntimeException('Chưa cấu hình thông số SMTP (SMTP_HOST, SMTP_USERNAME, SMTP_PASSWORD).');
            }
            $this->sendViaSmtp($recipient, $subject, $bodyHtml, $bodyText);
            $this->updateLog($logId, 'sent', null, $attempts);
            return ['sent' => true, 'status' => 'sent', 'error' => null, 'log_id' => $logId];
        } catch (Throwable $exception) {
            $err = $exception->getMessage();
            error_log('[VAST Mailer] Send failed (log #' . $logId . '): ' . $err);
            $this->updateLog($logId, 'failed', $err, $attempts);
            return ['sent' => false, 'status' => 'failed', 'error' => $err, 'log_id' => $logId];
        }
    }

    private function findByIdempotencyKey(string $key): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM email_logs WHERE idempotency_key = :k LIMIT 1');
        $stmt->execute(['k' => $key]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function recordLog(?int $userId, ?int $orderId, string $recipient, string $subject, string $templateCode, string $bodyHtml, string $bodyText, ?string $idempotencyKey, string $status, ?string $error): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO email_logs (user_id, order_id, recipient_email, subject, template_code, body_html, body_text, idempotency_key, status, error_message, attempts, last_attempt_at)
             VALUES (:user_id, :order_id, :recipient_email, :subject, :template_code, :body_html, :body_text, :idempotency_key, :status, :error_message, 1, NOW())'
        );
        $stmt->execute([
            'user_id' => $userId,
            'order_id' => $orderId,
            'recipient_email' => $recipient,
            'subject' => $subject,
            'template_code' => $templateCode,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'idempotency_key' => $idempotencyKey,
            'status' => $status,
            'error_message' => $error,
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function updateLog(int $logId, string $status, ?string $error, int $attempts): void
    {
        $stmt = $this->db->prepare(
            'UPDATE email_logs SET status = :status, error_message = :error, attempts = :attempts, last_attempt_at = NOW(), sent_at = NOW() WHERE id = :id'
        );
        $stmt->execute([
            'status' => $status,
            'error' => $error,
            'attempts' => $attempts,
            'id' => $logId,
        ]);
    }

    private function sendViaSmtp(string $to, string $subject, string $html, string $text): void
    {
        $host = trim((string)$this->config['host']);
        $port = (int)($this->config['port'] ?: 587);
        $encryption = strtolower(trim((string)($this->config['encryption'] ?? 'tls')));
        $username = trim((string)($this->config['username'] ?? ''));
        $password = (string)($this->config['password'] ?? '');
        $fromEmail = trim((string)($this->config['from_email'] ?: $username));
        $fromName = trim((string)($this->config['from_name'] ?: 'VAST STUDIO'));

        $transport = $encryption === 'ssl' ? 'ssl://' . $host : 'tcp://' . $host;
        $socket = @stream_socket_client($transport . ':' . $port, $errno, $errstr, 8);
        if (!$socket) {
            throw new RuntimeException("Không kết nối được SMTP {$host}:{$port} ({$errstr})");
        }
        stream_set_timeout($socket, 10);

        try {
            $this->expectCode($socket, [220]);
            $ehloHost = gethostname() ?: 'localhost';
            $this->sendCmd($socket, "EHLO {$ehloHost}", [250]);

            if ($encryption === 'tls') {
                $this->sendCmd($socket, 'STARTTLS', [220]);
                $cryptoMethods = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                    $cryptoMethods |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                }
                if (!@stream_socket_enable_crypto($socket, true, $cryptoMethods)) {
                    throw new RuntimeException('Không thể bật mã hóa STARTTLS với máy chủ SMTP.');
                }
                $this->sendCmd($socket, "EHLO {$ehloHost}", [250]);
            }

            if ($username !== '') {
                $this->sendCmd($socket, 'AUTH LOGIN', [334]);
                $this->sendCmd($socket, base64_encode($username), [334]);
                $this->sendCmd($socket, base64_encode($password), [235]);
            }

            $this->sendCmd($socket, "MAIL FROM:<{$fromEmail}>", [250]);
            $this->sendCmd($socket, "RCPT TO:<{$to}>", [250, 251]);
            $this->sendCmd($socket, 'DATA', [354]);

            $boundary = 'vast_' . bin2hex(random_bytes(12));
            $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
            $headers = [
                'Date: ' . date('r'),
                "From: {$encodedFromName} <{$fromEmail}>",
                "To: <{$to}>",
                "Subject: {$encodedSubject}",
                'MIME-Version: 1.0',
                "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            ];

            $body = "--{$boundary}\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($text))
                . "--{$boundary}\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($html))
                . "--{$boundary}--\r\n";

            $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
            $message = str_replace("\r\n.", "\r\n..", $message);
            fwrite($socket, $message . "\r\n.\r\n");
            $this->expectCode($socket, [250]);
            $this->sendCmd($socket, 'QUIT', [221, 250]);
        } finally {
            @fclose($socket);
        }
    }

    private function sendCmd($socket, string $command, array $expectedCodes): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->expectCode($socket, $expectedCodes);
    }

    private function expectCode($socket, array $expectedCodes): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }
        if ($response === '') {
            throw new RuntimeException('Máy chủ SMTP không phản hồi.');
        }
        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('SMTP lỗi (' . $code . '): ' . trim($response));
        }
        return $response;
    }

    private function loadConfig(): array
    {
        $env = $this->readDotEnv();
        $localFile = __DIR__ . '/smtp.local.php';
        $local = is_file($localFile) ? (array)require $localFile : [];

        $pick = static function (string $envKey, string $localKey, $default = '') use ($env, $local) {
            if (isset($local[$localKey]) && $local[$localKey] !== '') {
                return $local[$localKey];
            }
            $sysEnv = getenv($envKey);
            if ($sysEnv !== false && $sysEnv !== '') {
                return $sysEnv;
            }
            if (isset($env[$envKey]) && $env[$envKey] !== '') {
                return $env[$envKey];
            }
            return $default;
        };

        return [
            'host' => (string)$pick('SMTP_HOST', 'host', ''),
            'port' => (int)$pick('SMTP_PORT', 'port', 587),
            'encryption' => (string)$pick('SMTP_ENCRYPTION', 'encryption', 'tls'),
            'username' => (string)$pick('SMTP_USERNAME', 'username', ''),
            'password' => (string)$pick('SMTP_PASSWORD', 'password', ''),
            'from_email' => (string)$pick('SMTP_FROM_EMAIL', 'from_email', ''),
            'from_name' => (string)$pick('SMTP_FROM_NAME', 'from_name', 'VAST STUDIO'),
            'admin_email' => (string)$pick('ADMIN_NOTIFY_EMAIL', 'admin_email', ''),
        ];
    }

    private function readDotEnv(): array
    {
        $path = dirname(__DIR__) . '/.env';
        if (!is_file($path)) {
            return [];
        }
        $vars = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $trimmed, 2);
            $vars[trim($k)] = trim(trim($v), "\"'");
        }
        return $vars;
    }
}
