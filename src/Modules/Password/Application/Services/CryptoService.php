<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\Password\Application\Services;

final class CryptoService
{
    /** Lấy khoá bí mật từ Settings (hoặc wp-config) */
    public function get_key(): string
    {
        // Ưu tiên hằng số trong wp-config (an toàn nhất)
        if (defined('TMT_CRM_PASSWORD_KEY') && is_string(TMT_CRM_PASSWORD_KEY) && TMT_CRM_PASSWORD_KEY !== '') {
            return TMT_CRM_PASSWORD_KEY;
        }
        // fallback: dùng KEY SALT WordPress (ít khuyến nghị)
        return wp_salt('auth');
    }

    public function encrypt(string $plaintext): array
    {
        $key = hash('sha256', $this->get_key(), true);

        if (function_exists('sodium_crypto_secretbox')) {
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $cipher = sodium_crypto_secretbox($plaintext, $nonce, $key);
            return [
                'ciphertext' => base64_encode($cipher),
                'nonce' => base64_encode($nonce),
            ];
        }

        $nonce = random_bytes(12);
        $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
        return [
            'ciphertext' => base64_encode($cipher . $tag),
            'nonce' => base64_encode($nonce),
        ];
    }

    public function decrypt(string $ciphertext_b64, string $nonce_b64): ?string
    {
        $key = hash('sha256', $this->get_key(), true);
        $ciphertext = base64_decode($ciphertext_b64, true);
        $nonce = base64_decode($nonce_b64, true);

        if (function_exists('sodium_crypto_secretbox_open')) {
            $plain = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
            return $plain === false ? null : $plain;
        }

        $raw = $ciphertext;
        $data = substr($raw, 0, -16);
        $tag  = substr($raw, -16);
        return openssl_decrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag) ?: null;
    }
}
