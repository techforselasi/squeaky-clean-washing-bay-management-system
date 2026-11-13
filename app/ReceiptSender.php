<?php

/**
 * Builds and delivers digital receipts (SMS to Messages + WhatsApp share).
 */
class ReceiptSender
{
    public static function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        // Ghana local 0XXXXXXXXX → 233XXXXXXXXX
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = '233' . substr($digits, 1);
        }
        return $digits;
    }

    public static function isValidEmail(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function isValidPhone(string $raw): bool
    {
        $digits = self::normalizePhone($raw);
        return strlen($digits) >= 9 && strlen($digits) <= 15;
    }

    public static function plainText(array $receipt): string
    {
        $amount = 'GH₵ ' . number_format((float) ($receipt['total_amount'] ?? 0), 2);
        $method = strtoupper((string) ($receipt['payment_method'] ?? 'PAID'));
        $lines = [
            'Squeaky Clean — Receipt #' . ($receipt['id'] ?? ''),
            'Plate: ' . ($receipt['license_plate'] ?? ''),
            ($receipt['car_type'] ?? '') . ' · ' . ($receipt['bay_name'] ?? ''),
            'Service: ' . ($receipt['service_name'] ?? ''),
            'Washer: ' . ($receipt['washer_name'] ?? ''),
            'Paid: ' . $amount . ' (' . $method . ')',
            'Thank you.',
        ];
        return implode("\n", $lines);
    }

    public static function htmlBody(array $receipt): string
    {
        $amount = 'GH₵ ' . number_format((float) ($receipt['total_amount'] ?? 0), 2);
        $method = strtoupper((string) ($receipt['payment_method'] ?? 'PAID'));
        $plate = htmlspecialchars((string) ($receipt['license_plate'] ?? ''), ENT_QUOTES, 'UTF-8');
        $carType = htmlspecialchars((string) ($receipt['car_type'] ?? ''), ENT_QUOTES, 'UTF-8');
        $bay = htmlspecialchars((string) ($receipt['bay_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $service = htmlspecialchars((string) ($receipt['service_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $washer = htmlspecialchars((string) ($receipt['washer_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $id = (int) ($receipt['id'] ?? 0);
        $date = htmlspecialchars((string) ($receipt['paid_at'] ?? $receipt['created_at'] ?? date('Y-m-d H:i')), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Receipt #{$id}</title></head>
<body style="margin:0;padding:24px;background:#f4f7fa;font-family:Arial,sans-serif;color:#1a2a36;">
  <div style="max-width:520px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.08);">
    <div style="background:#4887B7;color:#fff;padding:24px;">
      <div style="font-size:12px;opacity:.85;">From</div>
      <div style="font-size:18px;font-weight:700;">Squeaky Clean Washing Bay</div>
      <div style="margin-top:16px;font-size:12px;opacity:.85;">Vehicle</div>
      <div style="font-size:16px;font-weight:700;">{$plate}</div>
      <div style="opacity:.9;font-size:13px;">{$carType}<br>{$bay}</div>
      <div style="margin-top:20px;font-size:28px;font-weight:800;">RECEIPT</div>
      <div style="opacity:.9;">#{$id} | {$date}</div>
      <div style="margin-top:12px;font-size:28px;font-weight:800;">{$amount}</div>
      <div style="display:inline-block;margin-top:8px;padding:4px 10px;border:2px solid rgba(255,255,255,.75);border-radius:6px;font-size:12px;font-weight:700;">{$method}</div>
    </div>
    <div style="padding:24px;">
      <table style="width:100%;border-collapse:collapse;">
        <tr>
          <td style="padding:10px 0;border-bottom:1px solid #e5eef4;">
            <strong>{$service}</strong><br>
            <span style="color:#6B8A9A;font-size:13px;">{$washer}</span>
          </td>
          <td style="padding:10px 0;border-bottom:1px solid #e5eef4;text-align:right;font-weight:700;">{$amount}</td>
        </tr>
      </table>
      <div style="margin-top:18px;display:flex;justify-content:space-between;font-weight:700;">
        <span>Amount paid</span><span>{$amount}</span>
      </div>
      <p style="margin-top:24px;color:#6B8A9A;font-size:13px;">Thank you for washing with Squeaky Clean.</p>
    </div>
  </div>
</body>
</html>
HTML;
    }

    public static function smsConfigured(): bool
    {
        if (SMS_DEMO_MODE || SMS_PROVIDER === 'demo') {
            return true;
        }
        $key = SMS_API_KEY;
        return $key !== '' && !str_contains($key, 'PASTE_');
    }

    /**
     * @return array{success:bool,message:string}
     */
    public static function sendSms(string $phone, array $receipt): array
    {
        if (!self::isValidPhone($phone)) {
            return ['success' => false, 'message' => 'Enter a valid customer phone number.'];
        }

        $to = self::normalizePhone($phone);
        $message = self::plainText($receipt);

        // Demo / offline test: no provider call — log locally so the UI still works.
        if (SMS_DEMO_MODE || SMS_PROVIDER === 'demo' || !self::smsConfigured()) {
            $logDir = dirname(__DIR__) . '/storage';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }
            $line = date('c') . " | to={$to} | " . str_replace("\n", ' | ', $message) . PHP_EOL;
            @file_put_contents($logDir . '/sms_outbox.log', $line, FILE_APPEND);

            if (!self::smsConfigured() && !SMS_DEMO_MODE) {
                return [
                    'success' => false,
                    'message' => 'SMS is not configured. Paste an Arkesel API key in config/sms.php (needs internet).',
                ];
            }

            return [
                'success' => true,
                'message' => 'Demo SMS saved for ' . $to . ' (not sent to phone — turn off SMS_DEMO_MODE and add API key for live Messages).',
            ];
        }

        $payload = [
            'sender'     => SMS_SENDER_ID,
            'message'    => $message,
            'recipients' => [$to],
        ];

        $ch = curl_init(SMS_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'api-key: ' . SMS_API_KEY,
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 30,
        ]);

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            return [
                'success' => false,
                'message' => 'Could not reach SMS provider (check internet): ' . $err,
            ];
        }

        $data = json_decode($raw, true);
        $status = strtolower((string) ($data['status'] ?? ''));
        if ($code >= 200 && $code < 300 && ($status === 'success' || isset($data['data']))) {
            return ['success' => true, 'message' => 'Receipt SMS sent to ' . $to . '.'];
        }

        $providerMsg = $data['message'] ?? $data['error'] ?? ('HTTP ' . $code);
        return [
            'success' => false,
            'message' => 'SMS provider error: ' . $providerMsg,
        ];
    }

    /**
     * WhatsApp click-to-chat note (PDF is downloaded separately — wa.me cannot attach files).
     */
    public static function whatsappUrl(string $phone, array $receipt): string
    {
        $digits = self::normalizePhone($phone);
        $id = (int) ($receipt['id'] ?? 0);
        $amount = 'GHS ' . number_format((float) ($receipt['total_amount'] ?? 0), 2);
        $text = "Squeaky Clean receipt #{$id}\n"
            . 'Plate: ' . ($receipt['license_plate'] ?? '') . "\n"
            . "Amount paid: {$amount}\n"
            . 'Please see the attached A5 PDF receipt.';
        return 'https://wa.me/' . $digits . '?text=' . rawurlencode($text);
    }
}
