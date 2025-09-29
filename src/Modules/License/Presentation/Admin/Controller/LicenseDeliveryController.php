<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\License\Presentation\Admin\Controller;

use TMT\CRM\Modules\License\Application\Services\DeliveryService;
use TMT\CRM\Modules\License\Application\DTO\CredentialDeliveryDTO;
use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialDeliveryRepository;

final class LicenseDeliveryController
{
    public static function register(): void
    {
        add_action('admin_post_tmt_license_delivery_log',    [self::class, 'handle_log']);
        add_action('admin_post_tmt_license_delivery_delete', [self::class, 'handle_delete']);
    }

    public static function handle_log(): void
    {
        if (!current_user_can('manage_options')) wp_die(__('Not allowed', 'tmt-crm'));
        check_admin_referer('tmt_license_delivery_log', '_wpnonce');

        $credential_id = isset($_POST['credential_id']) ? (int)$_POST['credential_id'] : 0;

        $dto = CredentialDeliveryDTO::from_array([
            'credential_id'            => $credential_id,
            'delivered_to_customer_id' => isset($_POST['delivered_to_customer_id']) && $_POST['delivered_to_customer_id'] !== '' ? (int)$_POST['delivered_to_customer_id'] : null,
            'delivered_to_company_id'  => isset($_POST['delivered_to_company_id'])  && $_POST['delivered_to_company_id']  !== '' ? (int)$_POST['delivered_to_company_id']  : null,
            'delivered_to_contact_id'  => isset($_POST['delivered_to_contact_id'])  && $_POST['delivered_to_contact_id']  !== '' ? (int)$_POST['delivered_to_contact_id']  : null,
            'delivered_to_email'       => isset($_POST['delivered_to_email'])       && $_POST['delivered_to_email']       !== '' ? sanitize_email((string)$_POST['delivered_to_email']) : null,
            'delivered_at'             => isset($_POST['delivered_at'])             && $_POST['delivered_at']             !== '' ? (string)$_POST['delivered_at'] : null,
            'channel'                  => isset($_POST['channel'])                  && $_POST['channel']                  !== '' ? sanitize_text_field((string)$_POST['channel']) : 'email',
            'delivery_note'            => isset($_POST['delivery_note'])            && $_POST['delivery_note']            !== '' ? sanitize_text_field((string)$_POST['delivery_note']) : null,
        ]);

        global $wpdb;
        $svc = new DeliveryService(new WpdbCredentialDeliveryRepository($wpdb));
        $new_id = $svc->log_delivery($dto);

        $redir = add_query_arg([
            'page' => 'tmt-crm-licenses-edit',
            'id'   => $credential_id,
            'tab'  => 'deliveries',
            'logged_delivery' => $new_id > 0 ? 1 : 0,
        ], admin_url('admin.php'));
        wp_safe_redirect($redir); exit;
    }

    public static function handle_delete(): void
    {
        if (!current_user_can('manage_options')) wp_die(__('Not allowed', 'tmt-crm'));
        check_admin_referer('tmt_license_delivery_delete', '_wpnonce');

        $credential_id = isset($_GET['credential_id']) ? (int)$_GET['credential_id'] : 0;
        $id            = isset($_GET['id'])            ? (int)$_GET['id']            : 0;

        global $wpdb;
        $repo = new WpdbCredentialDeliveryRepository($wpdb);
        $ok   = $repo->delete($id, get_current_user_id(), 'admin delete delivery');

        $redir = add_query_arg([
            'page' => 'tmt-crm-licenses-edit',
            'id'   => $credential_id,
            'tab'  => 'deliveries',
            'deleted_delivery' => $ok ? 1 : 0,
        ], admin_url('admin.php'));
        wp_safe_redirect($redir); exit;
    }
}
