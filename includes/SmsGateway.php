<?php

function load_sms_env() {
    $path = __DIR__ . '/../.env.sms';
    if (!file_exists($path)) return [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $config = [];
    foreach ($lines as $line) {
        if (substr(trim($line), 0, 1) === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$key, $val] = explode('=', $line, 2);
        $config[trim($key)] = trim($val);
    }
    return $config;
}

class SmsGateway {
    private array $env;

    public function __construct() {
        $this->env = load_sms_env();
    }

    public function isConfigured(): bool {
        $env = $this->env;
        return !empty($env['SMS_ENDPOINT'])
            && !empty($env['SMS_USERNAME'])
            && !empty($env['SMS_PASSWORD']);
    }

    public function isEnabled(): bool {
        return ($this->env['SMS_ENABLED'] ?? 'true') === 'true';
    }

    public function formatPhone(string $phone): string {
        $phone = trim($phone);
        if (preg_match('/^01\d{9}$/', $phone)) return '+88' . $phone;
        if (preg_match('/^8801\d{9}$/', $phone)) return '+' . $phone;
        if (preg_match('/^\+8801\d{9}$/', $phone)) return $phone;
        return $phone;
    }

    public function send(string $phone, string $message): array {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'SMS gateway is disabled.'];
        }
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'SMS gateway not configured.'];
        }

        $env      = $this->env;
        $endpoint = $env['SMS_ENDPOINT'];
        $user     = $env['SMS_USERNAME'];
        $pass     = $env['SMS_PASSWORD'];
        $device   = $env['SMS_DEVICE_ID'] ?? '';
        $authType = $env['SMS_AUTH_TYPE'] ?? 'basic';

        if ($authType === 'basic') {
            $authHeader = 'Basic ' . base64_encode("$user:$pass");
        } elseif ($authType === 'bearer') {
            $authHeader = 'Bearer ' . $pass;
        } else {
            $authHeader = $pass;
        }

        $phone = $this->formatPhone($phone);

        $body = [
            'message'     => $message,
            'phoneNumber' => $phone,
        ];
        if (!empty($device)) {
            $body['deviceId'] = $device;
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: ' . $authHeader,
            ],
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err !== '') {
            return ['success' => false, 'error' => "cURL error: {$err}"];
        }

        if ($httpCode !== 200 && $httpCode !== 202) {
            error_log("SMS failed [$httpCode]: $response");
            return ['success' => false, 'error' => "HTTP {$httpCode}"];
        }

        return ['success' => true, 'error' => null];
    }
}
