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
        if (!PolicyService::can_reveal()) {
            wp_send_json_error(['message' => __('Not allowed', 'tmt-crm')], 403);
        }

        check_ajax_referer('tmt_crm_license_reveal_secret_');

        $credential_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $field         = isset($_POST['field']) ? sanitize_text_field((string)$_POST['field']) : 'secret_primary';

        if (!in_array($field, ['secret_primary_encrypted', 'secret_secondary_encrypted'], true)) {
            wp_send_json_error(['message' => 'Invalid field'], 400);
        }

        global $wpdb;
        $repo = new WpdbCredentialRepository($wpdb);
        $dto  = $repo->find_by_id($credential_id);

        if (!$dto) {
            wp_send_json_error(['message' => 'Credential not found'], 404);
        }

        $secret = null;
        $crypto = new CryptoService();

        if ($field === 'secret_primary_encrypted') {
            $secret = $crypto->decrypt_secret($dto->secret_primary_encrypted ?? '');
        } elseif ($field === 'secret_secondary_encrypted') {
            $secret = $crypto->decrypt_secret($dto->secret_secondary_encrypted ?? '');
        }

        // Ghi log: user nào, thời điểm nào reveal
        error_log(sprintf('[LicenseSecretReveal] User %d revealed %s of credential %d at %s',
            get_current_user_id(), $field, $credential_id, current_time('mysql')));

        wp_send_json_success(['secret' => $secret]);
    }
}
