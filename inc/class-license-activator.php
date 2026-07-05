<?php

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('ONEDOWN_LICENSE_PAYLOAD_KEY')) {
    define('ONEDOWN_LICENSE_PAYLOAD_KEY', 'onedown-license-loader|20260626|v1');
}

if (! function_exists('onedown_load_encrypted_php_payload')) :
    function onedown_load_encrypted_php_payload(string $payloadFile, string $keySeed): void
    {
        static $loaded = array();

        $normalized = str_replace('\\', '/', $payloadFile);
        if (isset($loaded[$normalized])) {
            return;
        }

        if (! is_readable($payloadFile)) {
            error_log('Onedown encrypted loader: payload file missing - ' . $payloadFile);
            return;
        }

        if (! function_exists('openssl_decrypt')) {
            error_log('Onedown encrypted loader: OpenSSL extension is required.');
            return;
        }

        $bundle = require $payloadFile;
        if (! is_array($bundle)) {
            error_log('Onedown encrypted loader: invalid payload bundle.');
            return;
        }

        $cipher = isset($bundle['cipher']) ? (string) $bundle['cipher'] : '';
        $iv = base64_decode((string) ($bundle['iv'] ?? ''), true);
        $payload = base64_decode((string) ($bundle['payload'] ?? ''), true);
        $mac = base64_decode((string) ($bundle['mac'] ?? ''), true);

        if ($cipher !== 'aes-256-cbc' || $iv === false || $payload === false || $mac === false) {
            error_log('Onedown encrypted loader: payload bundle decode failed.');
            return;
        }

        $key = hash('sha256', $keySeed, true);
        $expectedMac = hash_hmac('sha256', $iv . $payload, $key, true);
        if (! hash_equals($mac, $expectedMac)) {
            error_log('Onedown encrypted loader: payload integrity check failed.');
            return;
        }

        $plain = openssl_decrypt($payload, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        if (! is_string($plain) || $plain === '') {
            error_log('Onedown encrypted loader: payload decrypt failed.');
            return;
        }

        if (strncmp($plain, "\xEF\xBB\xBF", 3) === 0) {
            $plain = substr($plain, 3);
        }

        $plain = preg_replace('/^\s*<\?php\s*/', '', $plain, 1) ?? $plain;
        $plain = preg_replace('/\?>\s*$/', '', $plain, 1) ?? $plain;

        $loaded[$normalized] = true;
        eval($plain);
    }
endif;

onedown_load_encrypted_php_payload(__DIR__ . DIRECTORY_SEPARATOR . 'class-license-activator.payload.php', ONEDOWN_LICENSE_PAYLOAD_KEY);
