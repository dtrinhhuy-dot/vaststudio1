<?php

require_once __DIR__ . '/OrderWorkflow.php';
require_once __DIR__ . '/../../includes/Mailer.php';

class SepayService
{
    private PDO $db;
    private Mailer $mailer;
    private array $localConfig = [];

    public function __construct(PDO $db, ?Mailer $mailer = null)
    {
        $this->db = $db;
        $this->mailer = $mailer ?? new Mailer($db);
        $localFile = dirname(__DIR__, 2) . '/includes/sepay.local.php';
        if (is_file($localFile)) {
            $loaded = require $localFile;
            if (is_array($loaded)) {
                $this->localConfig = $loaded;
            }
        }
    }

    public static function supportedBanks(): array
    {
        return [
            'Vietcombank' => 'Vietcombank (VCB)',
            'MBBank' => 'MB Bank (Quân Đội)',
            'Techcombank' => 'Techcombank (TCB)',
            'ACB' => 'ACB (Á Châu)',
            'VPBank' => 'VPBank',
            'TPBank' => 'TPBank',
            'BIDV' => 'BIDV',
            'VietinBank' => 'VietinBank (CTG)',
            'Sacombank' => 'Sacombank',
            'HDBank' => 'HDBank',
            'VIB' => 'VIB',
            'MSB' => 'MSB (Hàng Hải)',
            'OCB' => 'OCB (Phương Đông)',
            'SHB' => 'SHB',
            'SeABank' => 'SeABank',
            'Eximbank' => 'Eximbank',
            'Cake' => 'CAKE by VPBank',
            'Timo' => 'Timo Digital Bank',
        ];
    }

    public function getConfig(): array
    {
        $settings = $this->loadStoreSettings();

        $rawEnabled = $settings['sepayEnabled'] ?? ($this->localConfig['enabled'] ?? true);
        $enabled = filter_var($rawEnabled, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($enabled === null) {
            $enabled = true;
        }

        $bankNameSetting = trim((string)($settings['bankName'] ?? ''));
        $bankCode = trim((string)($this->firstNonEmpty(
            getenv('SEPAY_BANK_CODE'),
            $this->localConfig['bank_code'] ?? '',
            $settings['sepayBankCode'] ?? '',
            $this->inferBankCodeFromName($bankNameSetting),
            'Vietcombank'
        )));

        $bankAccount = trim((string)($this->firstNonEmpty(
            getenv('SEPAY_BANK_ACCOUNT'),
            $this->localConfig['bank_account'] ?? '',
            $settings['bankAccount'] ?? ''
        )));
        $bankAccountClean = preg_replace('/\s+/', '', $bankAccount) ?: '';

        $bankOwner = trim((string)($this->firstNonEmpty(
            getenv('SEPAY_BANK_OWNER'),
            $this->localConfig['bank_owner'] ?? '',
            $settings['bankOwner'] ?? '',
            'CONG TY TNHH VAST STUDIO VIETNAM'
        )));

        $prefix = strtoupper(trim((string)($this->firstNonEmpty(
            getenv('SEPAY_WEBHOOK_PREFIX'),
            $this->localConfig['prefix'] ?? '',
            $settings['sepayPrefix'] ?? '',
            'VAST'
        ))));
        $prefix = preg_replace('/[^A-Z0-9]/', '', $prefix) ?: 'VAST';

        $apiKey = trim((string)($this->firstNonEmpty(
            getenv('SEPAY_API_KEY'),
            $this->localConfig['api_key'] ?? '',
            $settings['sepayWebhookToken'] ?? ''
        )));

        $apiToken = trim((string)($this->firstNonEmpty(
            getenv('SEPAY_API_TOKEN'),
            $this->localConfig['api_token'] ?? '',
            $settings['sepayApiToken'] ?? ''
        )));

        $companyUrl = trim((string)($this->firstNonEmpty(
            getenv('SEPAY_COMPANY_URL'),
            $this->localConfig['company_url'] ?? '',
            $settings['sepayCompanyUrl'] ?? '',
            'https://my.sepay.vn/company/ordersuccess'
        )));

        $requireAuth = $apiKey !== '';
        if (isset($settings['sepayRequireAuth']) && $settings['sepayRequireAuth'] !== '') {
            $requireAuth = filter_var($settings['sepayRequireAuth'], FILTER_VALIDATE_BOOLEAN);
        } elseif (isset($this->localConfig['require_auth'])) {
            $requireAuth = (bool)$this->localConfig['require_auth'];
        }

        $banks = self::supportedBanks();

        return [
            'enabled' => $enabled,
            'companyUrl' => $companyUrl,
            'bankCode' => $bankCode,
            'bankName' => $bankNameSetting !== '' ? $bankNameSetting : ($banks[$bankCode] ?? $bankCode),
            'bankAccount' => $bankAccount,
            'bankAccountClean' => $bankAccountClean,
            'bankOwner' => $bankOwner,
            'prefix' => $prefix,
            'apiKey' => $apiKey,
            'apiToken' => $apiToken,
            'requireAuth' => $requireAuth,
            'webhookUrl' => $this->mailer->getBaseUrl() . '/sepay-webhook.php',
            'apiWebhookUrl' => $this->mailer->getBaseUrl() . '/api.php?action=sepay_webhook',
        ];
    }

    public function hasApiToken(): bool
    {
        return $this->getConfig()['apiToken'] !== '';
    }

    public function isLocalSandboxAllowed(): bool
    {
        $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $host = strtolower((string)parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST));
        return in_array($remoteAddress, ['127.0.0.1', '::1'], true)
            && in_array($host, ['localhost', '127.0.0.1', '[::1]'], true);
    }

    public function getPublicConfig(): array
    {
        $cfg = $this->getConfig();
        $isDemoAcc = $cfg['bankAccountClean'] === '' || $cfg['bankAccountClean'] === '998877665500';
        return [
            'enabled' => $cfg['enabled'],
            'companyUrl' => $cfg['companyUrl'],
            'bankCode' => $cfg['bankCode'],
            'bankName' => $cfg['bankName'],
            'bankAccount' => $cfg['bankAccount'],
            'bankAccountClean' => $cfg['bankAccountClean'],
            'bankOwner' => $cfg['bankOwner'],
            'prefix' => $cfg['prefix'],
            'webhookConfigured' => $cfg['apiKey'] !== '',
            'apiSyncConfigured' => $cfg['apiToken'] !== '',
            'isDemoAccount' => $isDemoAcc,
            'canLocalSimulate' => $this->isLocalSandboxAllowed() && ($isDemoAcc || ($cfg['apiToken'] === '' && $cfg['apiKey'] === '')),
        ];
    }

    public static function normalizeTransferOrderCode(string $orderCode): string
    {
        $clean = preg_replace('/[^A-Z0-9]/i', '', strtoupper(trim($orderCode))) ?? '';
        return $clean !== '' ? $clean : 'VAST';
    }

    public function buildQrData(array $order): array
    {
        $cfg = $this->getConfig();
        $orderCode = (string)($order['id'] ?? $order['order_code'] ?? '');
        $transferContent = self::normalizeTransferOrderCode($orderCode);
        $amount = max(0, (int)round((float)($order['total'] ?? $order['total_amount'] ?? 0)));
        $acc = $cfg['bankAccountClean'] !== '' ? $cfg['bankAccountClean'] : '998877665500';
        $bank = $cfg['bankCode'] !== '' ? $cfg['bankCode'] : 'Vietcombank';
        $owner = $cfg['bankOwner'] ?: 'CONG TY TNHH VAST STUDIO VIETNAM';

        $sepayQrUrl = 'https://qr.sepay.vn/img?' . http_build_query([
            'acc' => $acc,
            'bank' => $bank,
            'amount' => $amount,
            'des' => $transferContent,
            'template' => 'compact',
        ]);

        $fallbackQrUrl = 'https://img.vietqr.io/image/' . rawurlencode(strtolower($bank)) . '-' . rawurlencode($acc) . '-compact2.png?' . http_build_query([
            'amount' => $amount,
            'addInfo' => $transferContent,
            'accountName' => $owner,
        ]);

        $isDemo = $cfg['bankAccountClean'] === '' || $cfg['bankAccountClean'] === '998877665500';
        return [
            'enabled' => $cfg['enabled'],
            'qrUrl' => $sepayQrUrl,
            'fallbackQrUrl' => $fallbackQrUrl,
            'bankCode' => $bank,
            'bankName' => $cfg['bankName'],
            'bankAccount' => $cfg['bankAccount'] ?: $acc,
            'bankAccountClean' => $acc,
            'bankOwner' => $owner,
            'amount' => $amount,
            'transferContent' => $transferContent,
            'isDemoAccount' => $isDemo,
            'webhookConfigured' => $cfg['apiKey'] !== '',
            'apiSyncConfigured' => $cfg['apiToken'] !== '',
            'canLocalSimulate' => $this->isLocalSandboxAllowed() && ($isDemo || ($cfg['apiToken'] === '' && $cfg['apiKey'] === '')),
        ];
    }

    /**
     * Fetch linked bank accounts from SePay User API (supports v1 & v2 fallback).
     */
    public function getBankAccountsFromApi(bool $forceRefresh = false): array
    {
        $cfg = $this->getConfig();
        if ($cfg['apiToken'] === '') {
            return ['ok' => false, 'http_code' => 0, 'accounts' => [], 'error' => 'Chưa cấu hình SePay API Token'];
        }
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'http_code' => 0, 'accounts' => [], 'error' => 'PHP chưa bật extension cURL'];
        }

        if (!$forceRefresh && session_status() === PHP_SESSION_ACTIVE) {
            $cached = $_SESSION['sepay_bank_accounts_cache'] ?? null;
            if (is_array($cached) && (time() - (int)($cached['ts'] ?? 0)) < 60 && is_array($cached['payload'] ?? null)) {
                return $cached['payload'];
            }
        }

        $ch = curl_init('https://my.sepay.vn/userapi/bankaccounts/list');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $cfg['apiToken'],
            ],
        ]);
        $raw = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $httpCode !== 200) {
            return [
                'ok' => false,
                'http_code' => $httpCode,
                'accounts' => [],
                'error' => $curlErr ?: ('SePay API HTTP ' . $httpCode),
            ];
        }

        $decoded = json_decode((string)$raw, true);
        $rawAccounts = is_array($decoded['bankaccounts'] ?? null) ? $decoded['bankaccounts'] : [];
        $accounts = [];
        foreach ($rawAccounts as $acc) {
            if (!is_array($acc)) {
                continue;
            }
            $bankShort = trim((string)($acc['bank_short_name'] ?? $acc['bank_code'] ?? $acc['bank_full_name'] ?? ''));
            $inferred = $this->inferBankCodeFromName($bankShort);
            $accounts[] = [
                'id' => $acc['id'] ?? null,
                'bankCode' => $inferred !== '' ? $inferred : $bankShort,
                'bankShortName' => $bankShort,
                'bankFullName' => trim((string)($acc['bank_full_name'] ?? $bankShort)),
                'accountNumber' => trim((string)($acc['account_number'] ?? '')),
                'accountHolder' => trim((string)($acc['account_holder_name'] ?? '')),
                'accumulated' => isset($acc['accumulated']) ? (float)$acc['accumulated'] : null,
                'active' => (bool)($acc['active'] ?? true),
            ];
        }

        $payload = [
            'ok' => true,
            'http_code' => $httpCode,
            'accounts' => $accounts,
            'error' => null,
        ];

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['sepay_bank_accounts_cache'] = [
                'ts' => time(),
                'payload' => $payload,
            ];
        }

        return $payload;
    }

    /**
     * Poll SePay User API (https://my.sepay.vn/userapi/transactions/list) using API Token.
     * Works from localhost without public HTTPS webhook exposure.
     */
    public function syncRecentTransactionsFromApi(int $limit = 20, bool $force = false): array
    {
        $cfg = $this->getConfig();
        if ($cfg['apiToken'] === '' || !function_exists('curl_init')) {
            return ['synced' => false, 'reason' => 'api_token_not_configured'];
        }

        if (!$force && session_status() === PHP_SESSION_ACTIVE) {
            $lastPoll = (int)($_SESSION['sepay_last_api_poll'] ?? 0);
            if (time() - $lastPoll < 2) {
                return ['synced' => false, 'reason' => 'throttled'];
            }
            $_SESSION['sepay_last_api_poll'] = time();
        }

        $query = ['limit' => max(1, min(50, $limit))];
        if ($cfg['bankAccountClean'] !== '' && $cfg['bankAccountClean'] !== '998877665500') {
            $query['account_number'] = $cfg['bankAccountClean'];
        }
        $url = 'https://my.sepay.vn/userapi/transactions/list?' . http_build_query($query);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $cfg['apiToken'],
        ]);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return ['synced' => false, 'http_code' => $httpCode, 'error' => $curlErr ?: 'SePay API error'];
        }

        $decoded = json_decode((string)$response, true);
        $transactions = is_array($decoded['transactions'] ?? null) ? $decoded['transactions'] : [];
        $matched = [];

        foreach ($transactions as $tx) {
            $amtIn = (float)($tx['amount_in'] ?? 0);
            if ($amtIn <= 0) {
                continue;
            }
            $normalized = [
                'id' => $tx['id'] ?? null,
                'gateway' => (string)($tx['bank_brand_name'] ?? $cfg['bankCode'] ?? 'Bank'),
                'transactionDate' => (string)($tx['transaction_date'] ?? date('Y-m-d H:i:s')),
                'accountNumber' => (string)($tx['account_number'] ?? ''),
                'subAccount' => $tx['sub_account'] ?? null,
                'code' => (string)($tx['code'] ?? ''),
                'content' => (string)($tx['transaction_content'] ?? ''),
                'transferType' => 'in',
                'description' => (string)($tx['transaction_content'] ?? ''),
                'transferAmount' => $amtIn,
                'accumulated' => isset($tx['accumulated']) ? (float)$tx['accumulated'] : null,
                'referenceCode' => (string)($tx['reference_number'] ?? ''),
            ];
            $raw = json_encode($tx, JSON_UNESCAPED_UNICODE) ?: '{}';
            $res = $this->handleWebhook($normalized, $raw, false);
            if (!empty($res['order_code'])) {
                $matched[] = $res;
            }
        }

        return ['synced' => true, 'checked' => count($transactions), 'matched' => $matched];
    }

    public function verifyAuthorizationHeader(?string $authHeader, ?string $apiKeyHeader = null): bool
    {
        $cfg = $this->getConfig();
        $expected = $cfg['apiKey'];

        if ($expected === '') {
            return !$cfg['requireAuth'];
        }

        $candidates = array_filter([
            trim((string)$authHeader),
            trim((string)$apiKeyHeader),
        ], static fn (string $v): bool => $v !== '');

        foreach ($candidates as $raw) {
            if (preg_match('/^(?:Apikey|Bearer)\s+(.+)$/i', $raw, $m)) {
                $token = trim($m[1]);
                if (hash_equals($expected, $token)) {
                    return true;
                }
            } elseif (hash_equals($expected, $raw)) {
                return true;
            }
        }

        return false;
    }

    public function handleWebhook(array $payload, string $rawPayload, bool $isSimulation = false): array
    {
        $cfg = $this->getConfig();
        $sepayId = isset($payload['id']) && is_numeric($payload['id']) ? (int)$payload['id'] : null;
        if ($sepayId === null || $sepayId <= 0) {
            if ($isSimulation) {
                $sepayId = (int)(microtime(true) * 100);
            } else {
                throw new InvalidArgumentException('Thiếu mã giao dịch SePay (id).');
            }
        }

        $gateway = trim((string)($payload['gateway'] ?? ($isSimulation ? $cfg['bankCode'] : 'Bank')));
        $txDateRaw = trim((string)($payload['transactionDate'] ?? date('Y-m-d H:i:s')));
        $txTs = strtotime($txDateRaw);
        $txDate = $txTs !== false ? date('Y-m-d H:i:s', $txTs) : date('Y-m-d H:i:s');
        $accountNumber = trim((string)($payload['accountNumber'] ?? ''));
        $subAccount = isset($payload['subAccount']) && trim((string)$payload['subAccount']) !== ''
            ? trim((string)$payload['subAccount'])
            : null;
        $transferType = strtolower(trim((string)($payload['transferType'] ?? 'in')));
        $transferAmount = (float)($payload['transferAmount'] ?? $payload['amount_in'] ?? 0);
        $accumulated = isset($payload['accumulated']) && is_numeric($payload['accumulated'])
            ? (float)$payload['accumulated']
            : null;
        $parsedCode = trim((string)($payload['code'] ?? ''));
        $content = trim((string)($payload['content'] ?? ''));
        $description = trim((string)($payload['description'] ?? ''));
        $referenceCode = trim((string)($payload['referenceCode'] ?? ''));

        $this->db->beginTransaction();
        $emailToSend = null;

        try {
            $insTx = $this->db->prepare(
                "INSERT IGNORE INTO sepay_transactions
                 (sepay_id, reference_code, gateway, transaction_date, account_number, sub_account, transfer_type,
                  transfer_amount, accumulated, code, content, description, match_status, raw_payload)
                 VALUES
                 (:sid, :ref, :gw, :txd, :acc, :sub, :ttype, :amt, :accum, :code, :content, :desc, 'pending', :raw)"
            );
            $insTx->execute([
                'sid' => $sepayId,
                'ref' => $referenceCode !== '' ? $referenceCode : ('SEPAY-' . $sepayId),
                'gw' => $gateway,
                'txd' => $txDate,
                'acc' => $accountNumber,
                'sub' => $subAccount,
                'ttype' => $transferType,
                'amt' => $transferAmount,
                'accum' => $accumulated,
                'code' => $parsedCode !== '' ? $parsedCode : null,
                'content' => $content,
                'desc' => $description,
                'raw' => $rawPayload,
            ]);

            if ($insTx->rowCount() === 0) {
                $this->db->commit();
                return [
                    'success' => true,
                    'duplicate' => true,
                    'sepay_id' => $sepayId,
                    'message' => 'Giao dịch SePay đã được xử lý trước đó.',
                ];
            }

            $txDbId = (int)$this->db->lastInsertId();

            if ($transferType !== 'in' || $transferAmount <= 0) {
                $this->updateTransactionMatch($txDbId, null, null, 'ignored_out', 'Giao dịch không phải tiền vào.');
                $this->db->commit();
                return [
                    'success' => true,
                    'match_status' => 'ignored_out',
                    'message' => 'Bỏ qua giao dịch tiền ra hoặc số tiền bằng 0.',
                ];
            }

            $candidates = $this->extractOrderCodeCandidates($parsedCode, $content, $description, $cfg['prefix']);
            $order = $this->findAndLockMatchingOrder($candidates);

            if (!$order) {
                $note = 'Không tìm thấy đơn hàng khớp nội dung: ' . mb_substr($content ?: $parsedCode, 0, 180);
                $this->updateTransactionMatch($txDbId, null, null, 'unmatched', $note);
                $this->createNotification(
                    null,
                    'admin',
                    null,
                    'SePay nhận CK chưa khớp đơn',
                    'Nhận +' . $this->money($transferAmount) . " ({$gateway}) — Nội dung: " . ($content ?: $parsedCode),
                    'admin.php?tab=sepay'
                );
                $this->db->commit();
                return [
                    'success' => true,
                    'match_status' => 'unmatched',
                    'message' => $note,
                ];
            }

            $oid = (int)$order['id'];
            $orderCode = (string)$order['order_code'];
            $currentStatus = (string)$order['current_status'];

            if (in_array($currentStatus, [OrderWorkflow::STATUS_CANCELLED, OrderWorkflow::STATUS_RETURN_COMPLETED], true)) {
                $note = "Đơn #{$orderCode} đang ở trạng thái {$currentStatus}, không tự động ghi nhận thanh toán.";
                $this->updateTransactionMatch($txDbId, $oid, $orderCode, 'ignored_cancelled', $note);
                $this->createNotification(
                    null,
                    'admin',
                    $oid,
                    "SePay: CK vào đơn đã hủy #{$orderCode}",
                    "Nhận " . $this->money($transferAmount) . " cho đơn #{$orderCode} đã ở trạng thái hủy/hoàn tất đổi trả.",
                    'admin.php?tab=orders&order=' . rawurlencode($orderCode)
                );
                $this->db->commit();
                return ['success' => true, 'match_status' => 'ignored_cancelled', 'order_code' => $orderCode, 'message' => $note];
            }

            $payStmt = $this->db->prepare('SELECT * FROM payments WHERE order_id = :oid ORDER BY id DESC LIMIT 1 FOR UPDATE');
            $payStmt->execute(['oid' => $oid]);
            $payment = $payStmt->fetch();
            $paymentStatus = $payment ? (string)$payment['status'] : 'pending';

            if ($paymentStatus === 'paid') {
                $note = "Đơn #{$orderCode} đã được thanh toán trước đó.";
                $this->updateTransactionMatch($txDbId, $oid, $orderCode, 'already_paid', $note);
                $this->db->commit();
                return ['success' => true, 'match_status' => 'already_paid', 'order_code' => $orderCode, 'message' => $note];
            }

            $expectedAmount = (float)$order['total_amount'];
            $refDisplay = $referenceCode !== '' ? $referenceCode : ('SEPAY-' . $sepayId);

            if ($transferAmount + 0.01 < $expectedAmount) {
                $missing = max(0.0, $expectedAmount - $transferAmount);
                $note = 'Chuyển khoản thiếu ' . $this->money($missing) . ' (nhận ' . $this->money($transferAmount) . ' / cần ' . $this->money($expectedAmount) . ')';
                $this->updateTransactionMatch($txDbId, $oid, $orderCode, 'underpaid', $note);
                $this->logStatusHistory($oid, $currentStatus, $currentStatus, "SePay ({$gateway} - {$refDisplay}): {$note}", 'system', null, 'SePay Webhook');
                $this->createNotification(
                    null,
                    'admin',
                    $oid,
                    "SePay: Đơn #{$orderCode} chuyển thiếu tiền",
                    $note,
                    'admin.php?tab=orders&order=' . rawurlencode($orderCode)
                );
                $this->db->commit();
                return [
                    'success' => true,
                    'match_status' => 'underpaid',
                    'order_code' => $orderCode,
                    'message' => $note,
                ];
            }

            $matchStatus = ($transferAmount > $expectedAmount + 0.01) ? 'overpaid' : 'matched';
            $matchNote = ($isSimulation ? '[Giả lập] ' : '') . "Đã thanh toán {$this->money($transferAmount)} qua {$gateway} (Mã GD: {$refDisplay})";

            if ($payment) {
                $this->db->prepare(
                    "UPDATE payments SET status = 'paid', transaction_code = :tcode, updated_at = NOW() WHERE id = :pid"
                )->execute([
                    'tcode' => $refDisplay,
                    'pid' => (int)$payment['id'],
                ]);
            } else {
                $pmId = $this->resolveQrMethodId();
                $this->db->prepare(
                    "INSERT INTO payments (order_id, payment_method_id, amount, status, transaction_code)
                     VALUES (:oid, :pmid, :amt, 'paid', :tcode)"
                )->execute([
                    'oid' => $oid,
                    'pmid' => $pmId,
                    'amt' => $expectedAmount,
                    'tcode' => $refDisplay,
                ]);
            }

            $this->updateTransactionMatch($txDbId, $oid, $orderCode, $matchStatus, $matchNote);

            // Auto-confirm strictly from pending -> confirmed only (never skip steps or mark completed)
            $newStatus = $currentStatus;
            if ($currentStatus === OrderWorkflow::STATUS_PENDING) {
                $newStatus = OrderWorkflow::STATUS_CONFIRMED;
                $this->db->prepare(
                    'UPDATE orders SET current_status = :status, confirmed_at = COALESCE(confirmed_at, NOW()) WHERE id = :oid'
                )->execute([
                    'status' => $newStatus,
                    'oid' => $oid,
                ]);
            }

            $this->logStatusHistory(
                $oid,
                $currentStatus,
                $newStatus,
                $matchNote,
                'system',
                null,
                'SePay Webhook'
            );

            $custUid = (int)$order['user_id'];
            $this->createNotification(
                $custUid,
                'customer',
                $oid,
                "Thanh toán thành công đơn #{$orderCode}",
                "Hệ thống SePay đã xác nhận khoản chuyển " . $this->money($transferAmount) . ". Đơn hàng của bạn đã được xác nhận.",
                'auth.php?detail_order=' . rawurlencode($orderCode)
            );
            $this->createNotification(
                null,
                'admin',
                $oid,
                "SePay: Đơn #{$orderCode} đã thanh toán",
                $matchNote,
                'admin.php?tab=orders&order=' . rawurlencode($orderCode)
            );

            $emailToSend = [
                'order_numeric_id' => $oid,
                'order_code' => $orderCode,
                'user_id' => $custUid,
                'customer_name' => (string)($order['resolved_customer_name'] ?? $order['customer_name'] ?? 'Khách hàng'),
                'customer_email' => (string)($order['resolved_customer_email'] ?? $order['customer_email'] ?? ''),
                'amount' => $transferAmount,
                'gateway' => $gateway,
                'ref' => $refDisplay,
                'sepay_id' => $sepayId,
                'status_label' => OrderWorkflow::statusLabel($newStatus),
            ];

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        if ($emailToSend) {
            $this->sendPaymentSuccessEmails($emailToSend);
        }

        return [
            'success' => true,
            'match_status' => $matchStatus ?? 'matched',
            'order_code' => $orderCode ?? '',
            'payment_status' => 'paid',
            'sepay_id' => $sepayId,
            'message' => $matchNote ?? 'Thanh toán SePay thành công.',
        ];
    }

    public function simulateWebhookForOrder(string $orderCode, ?float $overrideAmount = null): array
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE order_code = :code LIMIT 1');
        $stmt->execute(['code' => trim($orderCode)]);
        $order = $stmt->fetch();
        if (!$order) {
            throw new InvalidArgumentException('Không tìm thấy đơn hàng "' . $orderCode . '" để giả lập thanh toán SePay.');
        }

        $cfg = $this->getConfig();
        $amount = $overrideAmount !== null ? $overrideAmount : (float)$order['total_amount'];
        $normCode = self::normalizeTransferOrderCode((string)$order['order_code']);
        $fakeId = (int)(microtime(true) * 1000) % 2000000000;
        $ref = 'FT' . date('YmdHis') . random_int(10, 99);

        $payload = [
            'id' => $fakeId,
            'gateway' => $cfg['bankCode'] ?: 'Vietcombank',
            'transactionDate' => date('Y-m-d H:i:s'),
            'accountNumber' => $cfg['bankAccountClean'] ?: '998877665500',
            'subAccount' => null,
            'code' => $normCode,
            'content' => "SEVQR {$normCode} Thanh toan don hang VAST STUDIO",
            'transferType' => 'in',
            'description' => "BankAPINotify SEVQR {$normCode}",
            'transferAmount' => $amount,
            'accumulated' => $amount,
            'referenceCode' => $ref,
        ];

        $raw = json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '{}';
        return $this->handleWebhook($payload, $raw, true);
    }

    public function getTransactions(string $filter = 'all', int $limit = 60): array
    {
        $sql = 'SELECT * FROM sepay_transactions WHERE 1 = 1';
        $params = [];
        if ($filter !== 'all') {
            $sql .= ' AND match_status = :status';
            $params['status'] = $filter;
        }
        $sql .= ' ORDER BY id DESC LIMIT ' . max(1, min(200, $limit));
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getOrderTransactions(string $orderCode): array
    {
        $stmt = $this->db->prepare('SELECT * FROM sepay_transactions WHERE order_code = :code ORDER BY id DESC');
        $stmt->execute(['code' => $orderCode]);
        return $stmt->fetchAll();
    }

    private function extractOrderCodeCandidates(string $parsedCode, string $content, string $description, string $prefix): array
    {
        $candidates = [];
        $push = static function (string $val) use (&$candidates): void {
            $norm = preg_replace('/[^A-Z0-9]/', '', strtoupper($val)) ?: '';
            if ($norm !== '' && !in_array($norm, $candidates, true)) {
                $candidates[] = $norm;
            }
        };

        if ($parsedCode !== '') {
            $push($parsedCode);
        }

        $haystack = strtoupper($parsedCode . ' ' . $content . ' ' . $description);
        $prefixes = array_unique(['VAST', $prefix]);
        foreach ($prefixes as $px) {
            if ($px === '') {
                continue;
            }
            $quoted = preg_quote($px, '/');
            if (preg_match_all('/' . $quoted . '[-_\s]*([A-Z0-9]{3,12})/i', $haystack, $matches)) {
                foreach ($matches[1] as $idx => $suffix) {
                    $push($px . $suffix);
                    if ($px !== 'VAST') {
                        $push('VAST' . $suffix);
                    }
                    $push($matches[0][$idx]);
                }
            }
        }

        return $candidates;
    }

    private function findAndLockMatchingOrder(array $normalizedCandidates): ?array
    {
        if (!$normalizedCandidates) {
            return null;
        }
        $stmt = $this->db->prepare(
            "SELECT o.*,
                    COALESCE(NULLIF(o.customer_name, ''), NULLIF(u.full_name, ''), u.email) AS resolved_customer_name,
                    COALESCE(NULLIF(o.customer_email, ''), u.email) AS resolved_customer_email
             FROM orders o
             LEFT JOIN users u ON u.id = o.user_id
             WHERE REPLACE(UPPER(o.order_code), '-', '') = :norm
             LIMIT 1 FOR UPDATE"
        );
        foreach ($normalizedCandidates as $cand) {
            $stmt->execute(['norm' => $cand]);
            $row = $stmt->fetch();
            if ($row) {
                return $row;
            }
        }
        return null;
    }

    private function updateTransactionMatch(int $txDbId, ?int $orderId, ?string $orderCode, string $status, string $note): void
    {
        $this->db->prepare(
            'UPDATE sepay_transactions SET order_id = :oid, order_code = :ocode, match_status = :st, note = :note WHERE id = :id'
        )->execute([
            'oid' => $orderId,
            'ocode' => $orderCode,
            'st' => $status,
            'note' => mb_substr($note, 0, 490),
            'id' => $txDbId,
        ]);
    }

    private function resolveQrMethodId(): int
    {
        $stmt = $this->db->prepare("SELECT id FROM payment_methods WHERE LOWER(code) = 'qr' LIMIT 1");
        $stmt->execute();
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int)$id;
        }
        $this->db->prepare("INSERT INTO payment_methods (code, name, is_active) VALUES ('qr', 'Chuyển khoản QR SePay', 1)")
            ->execute();
        return (int)$this->db->lastInsertId();
    }

    private function logStatusHistory(int $orderId, ?string $fromStatus, string $toStatus, string $note, string $actorType, ?int $actorId, string $actorName): void
    {
        $this->db->prepare(
            'INSERT INTO order_status_history (order_id, admin_id, from_status, actor_type, actor_id, actor_name, status, note)
             VALUES (:oid, NULL, :from_st, :atype, NULL, :aname, :st, :note)'
        )->execute([
            'oid' => $orderId,
            'from_st' => $fromStatus,
            'atype' => $actorType,
            'aname' => $actorName,
            'st' => $toStatus,
            'note' => $note,
        ]);
    }

    private function createNotification(?int $userId, string $role, ?int $orderId, string $title, string $message, string $link): void
    {
        $this->db->prepare(
            'INSERT INTO notifications (user_id, recipient_role, order_id, title, message, link)
             VALUES (:uid, :role, :oid, :title, :msg, :link)'
        )->execute([
            'uid' => $userId,
            'role' => $role,
            'oid' => $orderId,
            'title' => $title,
            'msg' => $message,
            'link' => $link,
        ]);
    }

    private function sendPaymentSuccessEmails(array $info): void
    {
        $baseUrl = $this->mailer->getBaseUrl();
        $orderCode = $info['order_code'];
        $amountFmt = $this->money((float)$info['amount']);

        if ($info['customer_email'] !== '') {
            $body = "<p>Xin chào <strong>" . htmlspecialchars($info['customer_name'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                <p>Hệ thống <strong>SePay</strong> đã ghi nhận thanh toán thành công cho đơn hàng <strong>#{$orderCode}</strong>:</p>
                <ul>
                    <li><strong>Số tiền đã nhận:</strong> <strong>{$amountFmt}</strong></li>
                    <li><strong>Ngân hàng / Cổng:</strong> " . htmlspecialchars($info['gateway'], ENT_QUOTES, 'UTF-8') . "</li>
                    <li><strong>Mã giao dịch:</strong> <code>" . htmlspecialchars($info['ref'], ENT_QUOTES, 'UTF-8') . "</code></li>
                    <li><strong>Trạng thái đơn hiện tại:</strong> <strong>" . htmlspecialchars($info['status_label'], ENT_QUOTES, 'UTF-8') . "</strong></li>
                </ul>";
            $this->mailer->sendTemplated([
                'user_id' => $info['user_id'],
                'order_id' => $info['order_numeric_id'],
                'recipient_email' => $info['customer_email'],
                'subject' => "[VAST STUDIO] Thanh toán thành công đơn #{$orderCode}",
                'template_code' => 'sepay_payment_confirmed',
                'idempotency_key' => 'sepay_paid_cust_' . $orderCode . '_' . $info['sepay_id'],
                'body_html' => Mailer::wrapHtml("Thanh toán thành công #{$orderCode}", $body, "{$baseUrl}/order-tracking.php?order=" . rawurlencode($orderCode), 'Xem tiến độ đơn hàng'),
            ]);
        }

        $adminBody = "<p>SePay Webhook vừa tự động khớp thanh toán đơn hàng <strong>#{$orderCode}</strong>:</p>
            <ul>
                <li><strong>Số tiền nhận:</strong> {$amountFmt}</li>
                <li><strong>Cổng ngân hàng:</strong> " . htmlspecialchars($info['gateway'], ENT_QUOTES, 'UTF-8') . "</li>
                <li><strong>Mã giao dịch:</strong> " . htmlspecialchars($info['ref'], ENT_QUOTES, 'UTF-8') . "</li>
            </ul>";
        $this->mailer->sendTemplated([
            'order_id' => $info['order_numeric_id'],
            'recipient_email' => $this->mailer->getAdminNotificationEmail(),
            'subject' => "[VAST STUDIO] SePay đã thu {$amountFmt} — Đơn #{$orderCode}",
            'template_code' => 'sepay_payment_admin',
            'idempotency_key' => 'sepay_paid_admin_' . $orderCode . '_' . $info['sepay_id'],
            'body_html' => Mailer::wrapHtml("SePay xác nhận thanh toán #{$orderCode}", $adminBody, "{$baseUrl}/admin.php?tab=orders&order=" . rawurlencode($orderCode), 'Mở đơn hàng trong Admin'),
        ]);
    }

    private function loadStoreSettings(): array
    {
        require_once __DIR__ . '/OrderService.php';
        return OrderService::readMergedSettings($this->db);
    }

    private function inferBankCodeFromName(string $bankName): string
    {
        if ($bankName === '') {
            return '';
        }
        foreach (array_keys(self::supportedBanks()) as $code) {
            if (stripos($bankName, $code) !== false) {
                return $code;
            }
        }
        if (stripos($bankName, 'mb') !== false || stripos($bankName, 'quân đội') !== false) {
            return 'MBBank';
        }
        if (stripos($bankName, 'công thương') !== false) {
            return 'VietinBank';
        }
        return '';
    }

    private function firstNonEmpty(...$values): string
    {
        foreach ($values as $v) {
            if ($v === false || $v === null) {
                continue;
            }
            $s = trim((string)$v);
            if ($s !== '') {
                return $s;
            }
        }
        return '';
    }

    private function money(float $amount): string
    {
        return number_format($amount, 0, ',', '.') . '₫';
    }
}
