<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Presentation\Admin\Controller;

use TMT\CRM\Modules\License\Application\Services\AllocationService;
use TMT\CRM\Modules\License\Application\Services\PolicyService;
use TMT\CRM\Modules\License\Application\DTO\CredentialSeatAllocationDTO;

use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialRepository;
use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialSeatAllocationRepository;
use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialActivationRepository;

final class LicenseAllocationController
{
    public static function register(): void
    {
        add_action('admin_post_tmt_license_allocation_save',   [self::class, 'handle_save']);
        add_action('admin_post_tmt_license_allocation_delete', [self::class, 'handle_delete']);
    }

    public static function handle_save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Not allowed', 'tmt-crm'));
        }
        check_admin_referer('tmt_license_allocation_save', '_wpnonce');

        $credential_id = isset($_POST['credential_id']) ? (int)$_POST['credential_id'] : 0;
        $allocation_id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;

        $dto = CredentialSeatAllocationDTO::from_array([
            'id'                => $allocation_id,
            'credential_id'     => $credential_id,
            'beneficiary_type'  => sanitize_text_field((string)($_POST['beneficiary_type'] ?? 'company')),
            'beneficiary_id'    => isset($_POST['beneficiary_id']) && $_POST['beneficiary_id'] !== '' ? (int)$_POST['beneficiary_id'] : null,
            'beneficiary_email' => isset($_POST['beneficiary_email']) && $_POST['beneficiary_email'] !== '' ? sanitize_text_field((string)$_POST['beneficiary_email']) : null,
            'seat_quota'        => isset($_POST['seat_quota']) ? max(0, (int)$_POST['seat_quota']) : 1,
            'seat_used'         => isset($_POST['seat_used']) ? max(0, (int)$_POST['seat_used']) : 0,
            'status'            => sanitize_text_field((string)($_POST['status'] ?? 'active')),
            'note'              => isset($_POST['note']) ? sanitize_text_field((string)$_POST['note']) : null,
            'invited_at'        => isset($_POST['invited_at']) && $_POST['invited_at'] !== '' ? sanitize_text_field((string)$_POST['invited_at']) : null,
            'accepted_at'       => isset($_POST['accepted_at']) && $_POST['accepted_at'] !== '' ? sanitize_text_field((string)$_POST['accepted_at']) : null,
            'revoked_at'        => isset($_POST['revoked_at']) && $_POST['revoked_at'] !== '' ? sanitize_text_field((string)$_POST['revoked_at']) : null,
        ]);

        global $wpdb;
        $service = new AllocationService(
            credential_repo: new WpdbCredentialRepository($wpdb),
            allocation_repo: new WpdbCredentialSeatAllocationRepository($wpdb),
            activation_repo: new WpdbCredentialActivationRepository($wpdb),
            policy: new PolicyService()
        );

        $ok = false;
        if ($allocation_id) {
            $ok = $service->update_allocation($allocation_id, $dto);
        } else {
            $new_id = $service->create_allocation($dto);
            $ok = $new_id > 0;
        }

        $redir = add_query_arg([
            'page' => 'tmt-crm-licenses-edit',
            'id'   => $credential_id,
            'tab'  => 'allocations',
            'saved_allocation' => $ok ? 1 : 0,
        ], admin_url('admin.php'));

        wp_safe_redirect($redir);
        exit;
    }

    public static function handle_delete(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Not allowed', 'tmt-crm'));
        }
        check_admin_referer('tmt_license_allocation_delete', '_wpnonce');

        $credential_id = isset($_GET['credential_id']) ? (int)$_GET['credential_id'] : 0;
        $allocation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        global $wpdb;
        $service = new AllocationService(
            credential_repo: new WpdbCredentialRepository($wpdb),
            allocation_repo: new WpdbCredentialSeatAllocationRepository($wpdb),
            activation_repo: new WpdbCredentialActivationRepository($wpdb),
            policy: new PolicyService()
        );

        $ok = $service->delete_allocation($allocation_id, get_current_user_id(), 'admin delete allocation');

        $redir = add_query_arg([
            'page' => 'tmt-crm-licenses-edit',
            'id'   => $credential_id,
            'tab'  => 'allocations',
            'deleted_allocation' => $ok ? 1 : 0,
        ], admin_url('admin.php'));

        wp_safe_redirect($redir);
        exit;
    }
}
