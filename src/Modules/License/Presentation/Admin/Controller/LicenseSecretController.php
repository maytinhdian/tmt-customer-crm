<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Presentation\Admin\Controller;

use TMT\CRM\Modules\License\Application\Services\PolicyService;
use TMT\CRM\Modules\License\Application\Services\CryptoService;
use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialRepository;

final class LicenseSecretController
{
    public static function register(): void
    {
        add_action('wp_ajax_tmt_crm_license_reveal_secret', [self::class, 'handle_reveal']);
    }

    public static function handle_reveal(): void
    {
        if (!\TMT\CRM\Modules\License\Application\Services\PolicyService::can_reveal()) {
            wp_send_json_error(['message' => __('Not allowed', 'tmt-crm')], 403);
            exit;
        }

        // Hỗ trợ cả '_ajax_nonce' lẫn 'nonce'
        $nonce_val = isset($_POST['_ajax_nonce']) ? (string) $_POST['_ajax_nonce'] : ((string) ($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce_val, 'tmt_crm_license_reveal_secret_')) {
            wp_send_json_error(['message' => __('Invalid nonce', 'tmt-crm')], 400);
            exit;
        }

        $credential_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $field_raw     = isset($_POST['field']) ? sanitize_text_field((string) $_POST['field']) : '';

        // Map field "thân thiện" -> cột DB
        $map = [
            'secret_primary'             => 'secret_primary_encrypted',
            'secret_secondary'           => 'secret_secondary_encrypted',
            'secret_primary_encrypted'   => 'secret_primary_encrypted',
            'secret_secondary_encrypted' => 'secret_secondary_encrypted',
        ];
        if (!$credential_id || !isset($map[$field_raw])) {
            wp_send_json_error(['message' => 'Invalid request'], 400);
            exit;
        }
        $db_field = $map[$field_raw];

        // Lấy repo qua DI (khuyến nghị)
        try {
            /** @var \TMT\CRM\Domain\Repositories\CredentialRepositoryInterface $repo */
            $repo = \TMT\CRM\Shared\Container\Container::get(\TMT\CRM\Domain\Repositories\CredentialRepositoryInterface::class);
        } catch (\Throwable $e) {
            // Fallback nếu DI chưa có binding (tránh chết trang)
            global $wpdb;
            $repo = new \TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialRepository($wpdb);
        }

        $dto = $repo->find_by_id($credential_id);
        if (!$dto) {
            wp_send_json_error(['message' => 'Credential not found'], 404);
            exit;
        }

        $cipher = (string) ($dto->secret_primary ?? '');
        if ($cipher === '') {
            wp_send_json_error(['message' => 'Empty secret'], 404);
            exit;
        }

        $crypto = new \TMT\CRM\Modules\License\Application\Services\CryptoService();
        $secret = $crypto->decrypt_secret($cipher);

        // Có thể trả về chuỗi rỗng nếu giải mã thất bại → coi là lỗi dữ liệu
        if ($secret === null) {
            wp_send_json_error(['message' => 'Decrypt failed'], 500);
            exit;
        }

        // Audit log đơn giản (sau này chuyển qua Core/AuditLog)
        error_log(sprintf(
            '[LicenseSecretReveal] user=%d ip=%s credential=%d field=%s at=%s',
            get_current_user_id(),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $credential_id,
            $db_field,
            current_time('mysql')
        ));

        wp_send_json_success(['secret' => $secret]);
        exit;
    }
}
