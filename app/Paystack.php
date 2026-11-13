<?php

class Paystack
{
    public static function isConfigured(): bool
    {
        if (!defined('PAYSTACK_SECRET_KEY') || !defined('PAYSTACK_PUBLIC_KEY')) {
            return false;
        }

        $secret = PAYSTACK_SECRET_KEY;
        $public = PAYSTACK_PUBLIC_KEY;

        if ($secret === '' || $public === '') {
            return false;
        }

        if (str_contains($secret, 'PASTE_HERE') || str_contains($public, 'PASTE_HERE')) {
            return false;
        }

        return true;
    }

    public static function initialize(array $payload): array
    {
        return self::request('POST', '/transaction/initialize', $payload);
    }

    public static function verify(string $reference): array
    {
        return self::request('GET', '/transaction/verify/' . rawurlencode($reference));
    }

    private static function request(string $method, string $path, ?array $payload = null): array
    {
        if (!self::isConfigured()) {
            return [
                'status'  => false,
                'message' => 'Paystack keys are not set. Paste your test keys into config/paystack.php.',
            ];
        }

        $url = 'https://api.paystack.co' . $path;
        $ch  = curl_init($url);

        $headers = [
            'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 45,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload ?? []));
        }

        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            return [
                'status'  => false,
                'message' => 'Could not reach Paystack: ' . $err,
            ];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [
                'status'  => false,
                'message' => 'Invalid response from Paystack (HTTP ' . $code . ').',
            ];
        }

        return $decoded;
    }
}
