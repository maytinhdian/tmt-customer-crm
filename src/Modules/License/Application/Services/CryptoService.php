<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Application\Services;

/**
 * CryptoService: mã hoá/giải mã secret + tạo mask.
 * P0: bạn có thể thay thế body bằng crypto thật (AES-256-GCM).
 */
final class CryptoService
{
    public function encrypt_secret(?string $plain): ?string
    {
        if ($plain === null || $plain === '') return null;
        // TODO: thay bằng crypto thực tế
        return base64_encode($plain);
    }

    public function decrypt_secret(?string $cipher): ?string
    {
        if ($cipher === null || $cipher === '') return null;
        // TODO: thay bằng crypto thực tế
        $decoded = base64_decode($cipher, true);
        return $decoded === false ? null : $decoded;
    }

    public function make_mask(?string $secret): ?string
    {
        if (!$secret) return null;
        $len = mb_strlen($secret);
        if ($len <= 4) return str_repeat('*', $len);
        return str_repeat('*', max(0, $len - 4)) . mb_substr($secret, -4);
    }
}
