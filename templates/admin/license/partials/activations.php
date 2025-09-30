<?php

/**
 * @var int $credential_id
 */

use TMT\CRM\Modules\License\Presentation\Admin\ListTable\ActivationListTable;
use TMT\CRM\Modules\License\Infrastructure\Persistence\{WpdbCredentialActivationRepository, WpdbCredentialSeatAllocationRepository};

defined('ABSPATH') || exit;

global $wpdb;
$actRepo   = new WpdbCredentialActivationRepository($wpdb);
$allocRepo = new WpdbCredentialSeatAllocationRepository($wpdb);

$items  = $actRepo->list_by_credential((int)$credential_id);
$allocs = $allocRepo->list_by_credential((int)$credential_id);

$action_url = admin_url('admin-post.php');

$table = new ActivationListTable();
$table->set_data($credential_id, $items);
$table->prepare_items();
?>
<div id="activations" class="tab-content" style="width:100%; padding:20px 0;">
    <h2><?php _e('Device Activations', 'tmt-crm'); ?></h2>

    <form method="post">
        <?php $table->display(); ?>
    </form>

    <hr />

    <h3><?php _e('Add Activation', 'tmt-crm'); ?></h3>
    <form method="post" action="<?php echo esc_url($action_url); ?>">
        <input type="hidden" name="action" value="tmt_license_activation_add" />
        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('tmt_license_activation_add')); ?>" />
        <input type="hidden" name="credential_id" value="<?php echo (int)$credential_id; ?>" />

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th><label for="allocation_id"><?php _e('Allocation', 'tmt-crm'); ?></label></th>
                    <td>
                        <select name="allocation_id" id="allocation_id">
                            <option value=""><?php _e('— none —', 'tmt-crm'); ?></option>
                            <?php foreach ($allocs as $a): ?>
                                <option value="<?php echo (int)$a->id; ?>">
                                    <?php
                                    $ben = $a->beneficiary_type . ($a->beneficiary_email ? ' - ' . $a->beneficiary_email : ($a->beneficiary_id ? ' #' . (int)$a->beneficiary_id : ''));
                                    printf('%s (used %d/%d)', esc_html($ben), (int)$a->seat_used, (int)$a->seat_quota);
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="hostname"><?php _e('Hostname', 'tmt-crm'); ?></label></th>
                    <td><input type="text" name="hostname" id="hostname" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="device_fingerprint_hash"><?php _e('Fingerprint (SHA-256)', 'tmt-crm'); ?></label></th>
                    <td><input type="text" name="device_fingerprint_hash" id="device_fingerprint_hash" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="user_display"><?php _e('User Display', 'tmt-crm'); ?></label></th>
                    <td><input type="text" name="user_display" id="user_display" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="user_email"><?php _e('User Email', 'tmt-crm'); ?></label></th>
                    <td><input type="email" name="user_email" id="user_email" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="location_hint"><?php _e('Location', 'tmt-crm'); ?></label></th>
                    <td><input type="text" name="location_hint" id="location_hint" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="os_info_json"><?php _e('OS Info (JSON)', 'tmt-crm'); ?></label></th>
                    <td><textarea name="os_info_json" id="os_info_json" class="large-text code" rows="4"></textarea></td>
                </tr>
                <tr>
                    <th><label for="note"><?php _e('Note', 'tmt-crm'); ?></label></th>
                    <td><input type="text" name="note" id="note" class="regular-text" /></td>
                </tr>
            </tbody>
        </table>

        <?php submit_button(__('Add Activation', 'tmt-crm')); ?>
    </form>

    <hr />

    <h3><?php _e('Transfer Activation', 'tmt-crm'); ?></h3>
    <form method="post" action="<?php echo esc_url($action_url); ?>">
        <input type="hidden" name="action" value="tmt_license_activation_transfer" />
        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('tmt_license_activation_transfer')); ?>" />
        <input type="hidden" name="credential_id" value="<?php echo (int)$credential_id; ?>" />

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th><label for="from_activation_id"><?php _e('From Activation ID', 'tmt-crm'); ?></label></th>
                    <td><input type="number" min="1" name="from_activation_id" id="from_activation_id" class="small-text" required /></td>
                </tr>
                <tr>
                    <th><label for="new_allocation_id"><?php _e('New Allocation', 'tmt-crm'); ?></label></th>
                    <td>
                        <select name="new_allocation_id" id="new_allocation_id">
                            <option value=""><?php _e('— none —', 'tmt-crm'); ?></option>
                            <?php foreach ($allocs as $a): ?>
                                <option value="<?php echo (int)$a->id; ?>">
                                    <?php
                                    $ben = $a->beneficiary_type . ($a->beneficiary_email ? ' - ' . $a->beneficiary_email : ($a->beneficiary_id ? ' #' . (int)$a->beneficiary_id : ''));
                                    printf('%s (used %d/%d)', esc_html($ben), (int)$a->seat_used, (int)$a->seat_quota);
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Để trống để transfer mà không gắn allocation.', 'tmt-crm'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="hostname"><?php _e('Hostname (new)', 'tmt-crm'); ?></label></th>
                    <td><input type="text" name="hostname" id="hostname" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="device_fingerprint_hash"><?php _e('Fingerprint (new)', 'tmt-crm'); ?></label></th>
                    <td><input type="text" name="device_fingerprint_hash" id="device_fingerprint_hash" class="regular-text" /></td>
                </tr>
            </tbody>
        </table>

        <?php submit_button(__('Transfer', 'tmt-crm')); ?>
    </form>
</div>