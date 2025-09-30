<?php

/**
 * @var int $credential_id
 */

use TMT\CRM\Modules\License\Presentation\Admin\ListTable\DeliveryListTable;
use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialDeliveryRepository;

defined('ABSPATH') || exit;

global $wpdb;
$repo      = new WpdbCredentialDeliveryRepository($wpdb);
$items     = $repo->list_by_credential((int)$credential_id);
$action_url = admin_url('admin-post.php');

$table = new DeliveryListTable();
$table->set_data($credential_id, $items);
$table->prepare_items();
?>
<div id="deliveries" class="tab-pane" style="width:100%; padding:0;">
    <h2 style="margin-top:20px;"><?php _e('Deliveries', 'tmt-crm'); ?></h2>

    <form method="post" style="margin:0;">
        <?php $table->display(); ?>
    </form>

    <hr style="margin:20px 0;" />

    <h3 style="margin-top:10px;"><?php _e('Log Delivery', 'tmt-crm'); ?></h3>
    <form method="post" action="<?php echo esc_url($action_url); ?>" style="max-width:100%;">
        <input type="hidden" name="action" value="tmt_license_delivery_log" />
        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('tmt_license_delivery_log')); ?>" />
        <input type="hidden" name="credential_id" value="<?php echo (int)$credential_id; ?>" />

        <table class="form-table" role="presentation" style="max-width:100%;">
            <tbody>
                <tr>
                    <th><label for="delivered_to_email"><?php _e('Delivered To (Email)', 'tmt-crm'); ?></label></th>
                    <td><input type="email" name="delivered_to_email" id="delivered_to_email" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="delivered_to_contact_id"><?php _e('Contact ID', 'tmt-crm'); ?></label></th>
                    <td><input type="number" name="delivered_to_contact_id" id="delivered_to_contact_id" class="small-text" /></td>
                </tr>
                <tr>
                    <th><label for="delivered_to_customer_id"><?php _e('Customer ID', 'tmt-crm'); ?></label></th>
                    <td><input type="number" name="delivered_to_customer_id" id="delivered_to_customer_id" class="small-text" /></td>
                </tr>
                <tr>
                    <th><label for="delivered_to_company_id"><?php _e('Company ID', 'tmt-crm'); ?></label></th>
                    <td><input type="number" name="delivered_to_company_id" id="delivered_to_company_id" class="small-text" /></td>
                </tr>
                <tr>
                    <th><label for="channel"><?php _e('Channel', 'tmt-crm'); ?></label></th>
                    <td>
                        <select name="channel" id="channel">
                            <?php foreach (['email', 'zalo', 'file', 'printed', 'other'] as $ch): ?>
                                <option value="<?php echo esc_attr($ch); ?>"><?php echo esc_html($ch); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="delivered_at"><?php _e('Delivered At', 'tmt-crm'); ?></label></th>
                    <td><input type="text" name="delivered_at" id="delivered_at" class="regular-text" placeholder="YYYY-MM-DD HH:MM:SS" /></td>
                </tr>
                <tr>
                    <th><label for="delivery_note"><?php _e('Note', 'tmt-crm'); ?></label></th>
                    <td><input type="text" name="delivery_note" id="delivery_note" class="regular-text" /></td>
                </tr>
            </tbody>
        </table>

        <?php submit_button(__('Log Delivery', 'tmt-crm')); ?>
    </form>
</div>