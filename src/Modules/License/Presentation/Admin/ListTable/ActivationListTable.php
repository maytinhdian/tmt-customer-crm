<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Presentation\Admin\ListTable;

use WP_List_Table;
use TMT\CRM\Modules\License\Application\DTO\CredentialActivationDTO;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class ActivationListTable extends WP_List_Table
{
    /** @var CredentialActivationDTO[] */
    private array $items_data = [];
    private int $credential_id = 0;

    public function __construct()
    {
        parent::__construct([
            'singular' => 'activation',
            'plural'   => 'activations',
            'ajax'     => false,
        ]);
    }

    public function set_data(int $credential_id, array $items): void
    {
        $this->credential_id = $credential_id;
        $this->items_data = $items;
    }

    public function get_columns(): array
    {
        return [
            'device'        => __('Device', 'tmt-crm'),
            'user'          => __('User', 'tmt-crm'),
            'allocation'    => __('Allocation', 'tmt-crm'),
            'status'        => __('Status', 'tmt-crm'),
            'activated_at'  => __('Activated', 'tmt-crm'),
            'last_seen_at'  => __('Last Seen', 'tmt-crm'),
            'source'        => __('Source', 'tmt-crm'),
            'note'          => __('Note', 'tmt-crm'),
        ];
    }

    protected function column_device($item): string
    {
        $device = $item->hostname ?: ($item->device_fingerprint_hash ? substr((string)$item->device_fingerprint_hash, 0, 10) . '…' : '-');

        $deact_url = wp_nonce_url(admin_url('admin-post.php'), 'tmt_license_activation_deactivate');
        $deact_form = sprintf(
            '<form style="display:inline;" method="post" action="%s">
                <input type="hidden" name="action" value="tmt_license_activation_deactivate"/>
                <input type="hidden" name="_wpnonce" value="%s"/>
                <input type="hidden" name="credential_id" value="%d"/>
                <input type="hidden" name="id" value="%d"/>
                <button class="button-link" onclick="return confirm(\'Deactivate this activation?\')">%s</button>
            </form>',
            esc_url($deact_url),
            wp_create_nonce('tmt_license_activation_deactivate'),
            (int)$this->credential_id,
            (int)$item->id,
            esc_html__('Deactivate', 'tmt-crm')
        );

        return '<strong>' . esc_html($device) . '</strong> ' . $deact_form;
    }

    protected function column_user($item): string
    {
        $u = trim(($item->user_display ?? '') . ' ' . ($item->user_email ?? ''));
        return esc_html($u !== '' ? $u : '-');
    }

    protected function column_allocation($item): string
    {
        return $item->allocation_id ? '#' . (int)$item->allocation_id : '-';
    }

    protected function column_status($item): string
    {
        return esc_html($item->status);
    }

    protected function column_activated_at($item): string
    {
        return esc_html($item->activated_at ?? '');
    }

    protected function column_last_seen_at($item): string
    {
        // Quick "Touch" button
        $touch_url = wp_nonce_url(admin_url('admin-post.php'), 'tmt_license_activation_touch');
        $touch_form = sprintf(
            '<form style="display:inline;margin-left:6px" method="post" action="%s">
                <input type="hidden" name="action" value="tmt_license_activation_touch"/>
                <input type="hidden" name="_wpnonce" value="%s"/>
                <input type="hidden" name="credential_id" value="%d"/>
                <input type="hidden" name="id" value="%d"/>
                <button class="button-link">%s</button>
            </form>',
            esc_url($touch_url),
            wp_create_nonce('tmt_license_activation_touch'),
            (int)$this->credential_id,
            (int)$item->id,
            esc_html__('Touch now', 'tmt-crm')
        );

        return esc_html($item->last_seen_at ?? '-') . $touch_form;
    }

    protected function column_source($item): string
    {
        return esc_html($item->source ?? 'manual');
    }

    protected function column_note($item): string
    {
        return esc_html($item->note ?? '');
    }

    public function no_items(): void
    {
        _e('No activations.', 'tmt-crm');
    }

    public function prepare_items(): void
    {
        $this->_column_headers = [$this->get_columns(), [], []];
        $this->items = $this->items_data;
    }
}
