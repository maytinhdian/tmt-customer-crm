<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Presentation\Admin\Controller;

use TMT\CRM\Modules\License\Application\Services\CryptoService;
use TMT\CRM\Modules\License\Application\Services\PolicyService;
use TMT\CRM\Modules\License\Application\Services\CredentialService;

use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialRepository;
use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialSeatAllocationRepository;
use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialActivationRepository;

use TMT\CRM\Modules\License\Application\DTO\CredentialDTO;
use TMT\CRM\Modules\License\Presentation\Admin\Screen\LicenseScreen;

final class LicenseController
{
    /** Action names cho admin-post */
    public const ACTION_SAVE        = 'tmt_crm_license_save';
    public const ACTION_SOFT_DELETE = 'tmt_crm_license_soft_delete';
    public const ACTION_RESTORE     = 'tmt_crm_license_restore';

    /** Nonce keys */
    private const NONCE_SAVE        = 'tmt_crm_license_save';
    private const NONCE_SOFT_DELETE = 'tmt_crm_license_soft_delete';
    private const NONCE_RESTORE     = 'tmt_crm_license_restore';

    public static function register(): void
    {

        // Hành vi GET trên màn hình (ví dụ: reveal)
        add_action('admin_init', [self::class, 'handle_actions']);

        // Routes POST (đã đăng nhập)
        add_action('admin_post_' . self::ACTION_SAVE,        [self::class, 'handle_save']);
        add_action('admin_post_' . self::ACTION_SOFT_DELETE, [self::class, 'handle_soft_delete']);
        add_action('admin_post_' . self::ACTION_RESTORE,     [self::class, 'restore']);

        // Chặn khách chưa đăng nhập
        add_action('admin_post_nopriv_' . self::ACTION_SAVE,        [self::class, 'forbid']);
        add_action('admin_post_nopriv_' . self::ACTION_SOFT_DELETE, [self::class, 'forbid']);
        add_action('admin_post_nopriv_' . self::ACTION_RESTORE,     [self::class, 'forbid']);
    }

    public static function handle_save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Not allowed', 'tmt-crm'));
        }
        check_admin_referer('tmt_license_save', '_wpnonce');

        // Build DTO
        $dto = CredentialDTO::from_array([
            'id'            => isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null,
            'number'        => sanitize_text_field((string)($_POST['number'] ?? '')),
            'type'          => sanitize_text_field((string)($_POST['type'] ?? 'LICENSE_KEY')),
            'label'         => sanitize_text_field((string)($_POST['label'] ?? '')),
            'customer_id'   => isset($_POST['customer_id']) && $_POST['customer_id'] !== '' ? (int)$_POST['customer_id'] : null,
            'company_id'    => isset($_POST['company_id']) && $_POST['company_id'] !== '' ? (int)$_POST['company_id'] : null,
            'status'        => sanitize_text_field((string)($_POST['status'] ?? 'active')),
            'expires_at'    => isset($_POST['expires_at']) && $_POST['expires_at'] !== '' ? sanitize_text_field((string)$_POST['expires_at']) : null,
            'seats_total'   => isset($_POST['seats_total']) && $_POST['seats_total'] !== '' ? (int)$_POST['seats_total'] : null,
            'sharing_mode'  => sanitize_text_field((string)($_POST['sharing_mode'] ?? 'none')),
            'renewal_of_id' => isset($_POST['renewal_of_id']) && $_POST['renewal_of_id'] !== '' ? (int)$_POST['renewal_of_id'] : null,
            'owner_id'      => isset($_POST['owner_id']) && $_POST['owner_id'] !== '' ? (int)$_POST['owner_id'] : null,
            'username'      => isset($_POST['username']) && $_POST['username'] !== '' ? sanitize_text_field((string)$_POST['username']) : null,
            // Lưu ý: secret_primary/secondary nhập plaintext; Service sẽ mã hoá
            'secret_primary'   => isset($_POST['secret_primary']) && $_POST['secret_primary'] !== '' ? (string)$_POST['secret_primary'] : null,
            'secret_secondary' => isset($_POST['secret_secondary']) && $_POST['secret_secondary'] !== '' ? (string)$_POST['secret_secondary'] : null,
            'extra_json'       => isset($_POST['extra_json']) && $_POST['extra_json'] !== '' ? (string)wp_unslash($_POST['extra_json']) : null,
            // secret_mask sẽ tự sinh nếu để trống
        ]);

        // Services + Repos
        global $wpdb;
        $svc = new CredentialService(
            credential_repo: new WpdbCredentialRepository($wpdb),
            allocation_repo: new WpdbCredentialSeatAllocationRepository($wpdb),
            activation_repo: new WpdbCredentialActivationRepository($wpdb),
            crypto: new CryptoService()
        );

        $id = $dto->id;
        if ($id) {
            $ok = $svc->update($id, $dto);
            $redir = add_query_arg(['page' => 'tmt-crm-licenses', 'updated' => $ok ? 1 : 0], admin_url('admin.php'));
        } else {
            $new_id = $svc->create($dto);
            $redir = add_query_arg(['page' => 'tmt-crm-licenses-edit', 'id' => $new_id, 'created' => $new_id > 0 ? 1 : 0], admin_url('admin.php'));
        }

        wp_safe_redirect($redir);
        exit;
    }

    public static function handle_delete(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Not allowed', 'tmt-crm'));
        }
        check_admin_referer('tmt_license_delete', '_wpnonce');

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        global $wpdb;
        $svc = new CredentialService(
            credential_repo: new WpdbCredentialRepository($wpdb),
            allocation_repo: new WpdbCredentialSeatAllocationRepository($wpdb),
            activation_repo: new WpdbCredentialActivationRepository($wpdb),
            crypto: new CryptoService()
        );

        $ok = $svc->soft_delete($id, get_current_user_id(), 'admin delete');
        $redir = add_query_arg(['page' => 'tmt-crm-licenses', 'deleted' => $ok ? 1 : 0], admin_url('admin.php'));

        wp_safe_redirect($redir);
        exit;
    }

    /** Redirect về trang danh sách */
    private static function redirect_back(array $args = []): void
    {
        $url = add_query_arg(array_merge([
            'page' => LicenseScreen::PAGE_SLUG,
        ], $args), admin_url('admin.php'));

        wp_safe_redirect($url);
        exit;
    }
}
