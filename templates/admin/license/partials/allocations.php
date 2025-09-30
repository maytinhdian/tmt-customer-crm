<?php

/**
 * @var int $credential_id
 */

use TMT\CRM\Modules\License\Presentation\Admin\ListTable\AllocationListTable;
use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialSeatAllocationRepository;

defined('ABSPATH') || exit;

global $wpdb;
$allocRepo = new WpdbCredentialSeatAllocationRepository($wpdb);
$items     = $allocRepo->list_by_credential((int)$credential_id);

$edit_id = isset($_GET['edit_allocation']) ? (int)$_GET['edit_allocation'] : 0;
$editing = null;
if ($edit_id) {
    foreach ($items as $a) {
        if ((int)$a->id === $edit_id) {
            $editing = $a;
            break;
        }
    }
}

$action_url = admin_url('admin-post.php');
$nonce_save = wp_create_nonce('tmt_license_allocation_save');

$table = new AllocationListTable();
$table->set_data($items);
$table->prepare_items();
?>
<div id="allocations" class="tab-content" style="width:100%; padding:20px 0;">
    <h2><?php _e('Seat Allocations', 'tmt-crm'); ?></h2>

    <form method="post" style="margin:0;">
        <?php $table->display(); ?>
    </form>

    <hr />

    <h3><?php echo $editing ? esc_html__('Edit Allocation', 'tmt-crm') : esc_html__('Add Allocation', 'tmt-crm'); ?></h3>
    <form method="post" action="<?php echo esc_url($action_url); ?>">
        <input type="hidden" name="action" value="tmt_license_allocation_save" />
        <input type="hidden" name="credential_id" value="<?php echo (int)$credential_id; ?>" />
        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_save); ?>" />
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?php echo (int)$editing->id; ?>" />
        <?php endif; ?>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th><label for="beneficiary_type"><?php _e('Beneficiary Type', 'tmt-crm'); ?></label></th>
                    <td>
                        <select name="beneficiary_type" id="beneficiary_type">
                            <?php
                            $types = ['company', 'customer', 'contact', 'email'];
                            $val   = $editing ? $editing->beneficiary_type : 'company';
                            foreach ($types as $t) {
                                printf(
                                    '<option value="%s" %s>%s</option>',
                                    esc_attr($t),
                                    selected($val, $t, false),
                                    esc_html($t)
                                );
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="beneficiary_id"><?php _e('Beneficiary ID', 'tmt-crm'); ?></label></th>
                    <td>
                        <input type="number" name="beneficiary_id" id="beneficiary_id" class="small-text"
                            value="<?php echo esc_attr((string)($editing->beneficiary_id ?? '')); ?>" />
                        <p class="description"><?php _e('Để trống nếu dùng email', 'tmt-crm'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="beneficiary_email"><?php _e('Beneficiary Email', 'tmt-crm'); ?></label></th>
                    <td>
                        <input type="email" name="beneficiary_email" id="beneficiary_email" class="regular-text"
                            value="<?php echo esc_attr((string)($editing->beneficiary_email ?? '')); ?>" />
                        <p class="description"><?php _e('Dùng cho Family Share', 'tmt-crm'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="seat_quota"><?php _e('Seat Quota', 'tmt-crm'); ?></label></th>
                    <td><input type="number" min="0" name="seat_quota" id="seat_quota" class="small-text"
                            value="<?php echo esc_attr((string)($editing->seat_quota ?? 1)); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="status"><?php _e('Status', 'tmt-crm'); ?></label></th>
                    <td>
                        <select name="status" id="status">
                            <?php
                            $statuses = ['pending', 'active', 'revoked'];
                            $val      = $editing ? $editing->status : 'active';
                            foreach ($statuses as $s) {
                                printf(
                                    '<option value="%s" %s>%s</option>',
                                    esc_attr($s),
                                    selected($val, $s, false),
                                    esc_html($s)
                                );
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="note"><?php _e('Note', 'tmt-crm'); ?></label></th>
                    <td><input type="text" name="note" id="note" class="regular-text"
                            value="<?php echo esc_attr((string)($editing->note ?? '')); ?>" /></td>
                </tr>
            </tbody>
        </table>

        <?php submit_button($editing ? __('Update Allocation', 'tmt-crm') : __('Add Allocation', 'tmt-crm')); ?>
    </form>
</div>