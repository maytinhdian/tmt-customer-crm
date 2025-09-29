<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Presentation\Admin\Controller;

use TMT\CRM\Modules\License\Application\Services\ActivationService;
use TMT\CRM\Modules\License\Application\Services\PolicyService;
use TMT\CRM\Modules\License\Application\DTO\CredentialActivationDTO;

use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialRepository;
use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialSeatAllocationRepository;
use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialActivationRepository;

final class LicenseActivationController
{
    public static function register(): void
    {
        add_action('admin_post_tmt_license_activation_add',        [self::class, 'handle_add']);
        add_action('admin_post_tmt_license_activation_deactivate', [self::class, 'handle_deactivate']);
        add_action('admin_post_tmt_license_activation_transfer',   [self::class, 'handle_transfer']);
        add_action('admin_post_tmt_license_activation_touch',      [self::class, 'handle_touch']);
    }

    private static function make_service(): ActivationService
    {
        global $wpdb;
        return new ActivationService(
            credential_repo: new WpdbCredentialRepository($wpdb),
            allocation_repo: new WpdbCredentialSeatAllocationRepository($wpdb),
            activation_repo: new WpdbCredentialActivationRepository($wpdb),
            policy: new PolicyService()
        );
    }

    public static function handle_add(): void
    {
        if (!current_user_can('manage_options')) wp_die(__('Not allowed', 'tmt-crm'));
        check_admin_referer('tmt_license_activation_add', '_wpnonce');

        $credential_id = isset($_POST['credential_id']) ? (int)$_POST['credential_id'] : 0;

        $dto = CredentialActivationDTO::from_array([
            'credential_id'           => $credential_id,
            'allocation_id'           => isset($_POST['allocation_id']) && $_POST['allocation_id'] !== '' ? (int)$_POST['allocation_id'] : null,
            'device_fingerprint_hash' => isset($_POST['device_fingerprint_hash']) && $_POST['device_fingerprint_hash'] !== '' ? (string)$_POST['device_fingerprint_hash'] : null,
            'hostname'                => isset($_POST['hostname']) && $_POST['hostname'] !== '' ? sanitize_text_field((string)$_POST['hostname']) : null,
            'os_info_json'            => isset($_POST['os_info_json']) && $_POST['os_info_json'] !== '' ? (string)wp_unslash($_POST['os_info_json']) : null,
            'location_hint'           => isset($_POST['location_hint']) && $_POST['location_hint'] !== '' ? sanitize_text_field((string)$_POST['location_hint']) : null,
            'user_display'            => isset($_POST['user_display']) && $_POST['user_display'] !== '' ? sanitize_text_field((string)$_POST['user_display']) : null,
            'user_email'              => isset($_POST['user_email']) && $_POST['user_email'] !== '' ? sanitize_email((string)$_POST['user_email']) : null,
            'status'                  => 'active',
            'activated_at'            => isset($_POST['activated_at']) && $_POST['activated_at'] !== '' ? (string)$_POST['activated_at'] : null,
            'source'                  => isset($_POST['source']) && $_POST['source'] !== '' ? sanitize_text_field((string)$_POST['source']) : 'manual',
            'note'                    => isset($_POST['note']) && $_POST['note'] !== '' ? sanitize_text_field((string)$_POST['note']) : null,
            'created_by'              => get_current_user_id(),
        ]);

        $svc = self::make_service();
        $new_id = $svc->add_activation($dto);

        $redir = add_query_arg([
            'page' => 'tmt-crm-licenses-edit',
            'id'   => $credential_id,
            'tab'  => 'activations',
            'added_activation' => $new_id > 0 ? 1 : 0,
        ], admin_url('admin.php'));
        wp_safe_redirect($redir);
        exit;
    }

    public static function handle_deactivate(): void
    {
        if (!current_user_can('manage_options')) wp_die(__('Not allowed', 'tmt-crm'));
        check_admin_referer('tmt_license_activation_deactivate', '_wpnonce');

        $credential_id = isset($_POST['credential_id']) ? (int)$_POST['credential_id'] : 0;
        $activation_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        $svc = self::make_service();
        $ok  = $svc->deactivate($activation_id, $_POST['deactivated_at'] ?? null);

        $redir = add_query_arg([
            'page' => 'tmt-crm-licenses-edit',
            'id'   => $credential_id,
            'tab'  => 'activations',
            'deactivated_activation' => $ok ? 1 : 0,
        ], admin_url('admin.php'));
        wp_safe_redirect($redir);
        exit;
    }

    public static function handle_transfer(): void
    {
        if (!current_user_can('manage_options')) wp_die(__('Not allowed', 'tmt-crm'));
        check_admin_referer('tmt_license_activation_transfer', '_wpnonce');

        $credential_id = isset($_POST['credential_id']) ? (int)$_POST['credential_id'] : 0;
        $from_id       = isset($_POST['from_activation_id']) ? (int)$_POST['from_activation_id'] : 0;

        $new_dto = CredentialActivationDTO::from_array([
            'credential_id'           => $credential_id,
            'allocation_id'           => isset($_POST['new_allocation_id']) && $_POST['new_allocation_id'] !== '' ? (int)$_POST['new_allocation_id'] : null,
            'device_fingerprint_hash' => isset($_POST['device_fingerprint_hash']) && $_POST['device_fingerprint_hash'] !== '' ? (string)$_POST['device_fingerprint_hash'] : null,
            'hostname'                => isset($_POST['hostname']) && $_POST['hostname'] !== '' ? sanitize_text_field((string)$_POST['hostname']) : null,
            'os_info_json'            => isset($_POST['os_info_json']) && $_POST['os_info_json'] !== '' ? (string)wp_unslash($_POST['os_info_json']) : null,
            'location_hint'           => isset($_POST['location_hint']) && $_POST['location_hint'] !== '' ? sanitize_text_field((string)$_POST['location_hint']) : null,
            'user_display'            => isset($_POST['user_display']) && $_POST['user_display'] !== '' ? sanitize_text_field((string)$_POST['user_display']) : null,
            'user_email'              => isset($_POST['user_email']) && $_POST['user_email'] !== '' ? sanitize_email((string)$_POST['user_email']) : null,
            'status'                  => 'active',
            'activated_at'            => isset($_POST['activated_at']) && $_POST['activated_at'] !== '' ? (string)$_POST['activated_at'] : null,
            'source'                  => 'manual',
            'note'                    => isset($_POST['note']) && $_POST['note'] !== '' ? sanitize_text_field((string)$_POST['note']) : null,
            'created_by'              => get_current_user_id(),
        ]);

        $svc   = self::make_service();
        $new_id = $svc->transfer($from_id, $new_dto);

        $redir = add_query_arg([
            'page' => 'tmt-crm-licenses-edit',
            'id'   => $credential_id,
            'tab'  => 'activations',
            'transferred_activation' => $new_id > 0 ? 1 : 0,
        ], admin_url('admin.php'));
        wp_safe_redirect($redir);
        exit;
    }

    public static function handle_touch(): void
    {
        if (!current_user_can('manage_options')) wp_die(__('Not allowed', 'tmt-crm'));
        check_admin_referer('tmt_license_activation_touch', '_wpnonce');

        $credential_id = isset($_POST['credential_id']) ? (int)$_POST['credential_id'] : 0;
        $activation_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        $svc = self::make_service();
        $ok  = $svc->touch_last_seen($activation_id, $_POST['last_seen_at'] ?? null);

        $redir = add_query_arg([
            'page' => 'tmt-crm-licenses-edit',
            'id'   => $credential_id,
            'tab'  => 'activations',
            'touched_activation' => $ok ? 1 : 0,
        ], admin_url('admin.php'));
        wp_safe_redirect($redir);
        exit;
    }
}
