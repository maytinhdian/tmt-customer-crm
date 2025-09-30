<?php

/**
 * @var string $action
 * @var array  $general
 */
defined('ABSPATH') || exit;

$id            = (int)($general['id'] ?? 0);
$number        = (string)($general['number'] ?? '');
$type          = (string)($general['type'] ?? 'LICENSE_KEY');
$label         = (string)($general['label'] ?? '');
$customer_id   = (string)($general['customer_id'] ?? '');
$company_id    = (string)($general['company_id'] ?? '');
$status        = (string)($general['status'] ?? 'active');
$expires_at    = (string)($general['expires_at'] ?? '');
$seats_total   = (string)($general['seats_total'] ?? '');
$sharing_mode  = (string)($general['sharing_mode'] ?? 'none');
$renewal_of_id = (string)($general['renewal_of_id'] ?? '');
$owner_id      = (string)($general['owner_id'] ?? '');
$username      = (string)($general['username'] ?? '');
$extra_json    = (string)($general['extra_json'] ?? '');
?>
<form method="post" action="<?php echo esc_url($action); ?>">
    <?php wp_nonce_field('tmt_crm_license_save_'); ?>
    <input type="hidden" name="action" value="tmt_crm_license_save" />
    <?php if ($id): ?>
        <input type="hidden" name="id" value="<?php echo (int)$id; ?>" />
    <?php endif; ?>

    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th><label for="number"><?php _e('Number', 'tmt-crm'); ?></label></th>
                <td>
                    <input name="number" id="number" type="text" class="regular-text"
                        value="<?php echo esc_attr($number); ?>"
                        placeholder="<?php echo esc_attr__('(auto)', 'tmt-crm'); ?>" />
                    <p class="description">
                        <?php _e('Để trống để hệ thống tự sinh số theo Core/Numbering.', 'tmt-crm'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="label"><?php _e('Label', 'tmt-crm'); ?></label></th>
                <td><input name="label" id="label" type="text" class="regular-text" value="<?php echo esc_attr($label); ?>" required /></td>
            </tr>
            <tr>
                <th><label for="type"><?php _e('Type', 'tmt-crm'); ?></label></th>
                <td>
                    <select name="type" id="type">
                        <?php foreach (['LICENSE_KEY', 'EMAIL_ACCOUNT', 'SAAS_ACCOUNT', 'API_TOKEN', 'WIFI_ACCOUNT', 'OTHER'] as $t): ?>
                            <option value="<?php echo esc_attr($t); ?>" <?php selected($type, $t); ?>><?php echo esc_html($t); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="status"><?php _e('Status', 'tmt-crm'); ?></label></th>
                <td>
                    <select name="status" id="status">
                        <?php foreach (['active', 'disabled', 'expired', 'revoked', 'pending'] as $st): ?>
                            <option value="<?php echo esc_attr($st); ?>" <?php selected($status, $st); ?>><?php echo esc_html($st); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="expires_at"><?php _e('Expires At', 'tmt-crm'); ?></label></th>
                <td><input name="expires_at" id="expires_at" type="text" class="regular-text" placeholder="(YYYY-MM-DD)" value="<?php echo esc_attr($expires_at); ?>" />
                    <p class="description">
                        <?php _e('Để trống thì mặc định là 365 ngày kể từ ngày lưu.', 'tmt-crm'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="seats_total"><?php _e('Seats Total', 'tmt-crm'); ?></label></th>
                <td><input name="seats_total" id="seats_total" type="number" min="1" max="50" default="1" class="small-text" value="<?php echo esc_attr($seats_total); ?>" /></td>
            </tr>
            <tr>
                <th><label for="sharing_mode"><?php _e('Sharing Mode', 'tmt-crm'); ?></label></th>
                <td>
                    <select name="sharing_mode" id="sharing_mode">
                        <?php foreach (['none', 'seat_allocation', 'family_share'] as $m): ?>
                            <option value="<?php echo esc_attr($m); ?>" <?php selected($sharing_mode, $m); ?>><?php echo esc_html($m); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th> <label for="customer_id" style="display:block;"><?php esc_html_e('Khách Hàng Cá Nhân', 'tmt-crm'); ?></label></th>
                <td>
                    <select id="customer_id" name="customer_id" style="min-width:320px;">
                        <?php if ($customer_id > 0): ?>
                            <option value="<?php echo (int)$customer_id; ?>" selected><?php echo esc_html(get_post_meta($customer_id, '_customer_name', true) ?: 'ID #' . $customer_id); ?></option>
                        <?php endif; ?>
                    </select>
                    <p class="description"><?php esc_html_e('Chọn 1 trong 2 : Cá Nhân hoặc Doanh Nghiệp.', 'tmt-crm'); ?></p>
                    </div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="company_id" style="display: block;"><strong><?php esc_html_e('Khách Hàng Doanh Nghiệp', 'tmt-crm'); ?></strong></label>
                </th>
                <td>
                    <select id="company_id" name="company_id"
                        data-placeholder="Chọn công ty..."
                        data-ajax-action="tmt_crm_search_companies"
                        data-initial-id="1">
                        <?php if (!empty($company_id)) : ?>
                            <option value="<?php echo (int)$company_id; ?>" selected>
                                <?php echo esc_html($company_name ?? ('ID #' . $company_id)); ?>
                            </option>
                        <?php endif; ?>
                    </select>
                    <p class="description"><?php esc_html_e('Chọn 1 trong 2 : Doanh Nghiệp hoặc Cá Nhân.', 'tmt-crm'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="owner_id"><?php _e('Owner ID', 'tmt-crm'); ?></label></th>
                <td><input name="owner_id" id="owner_id" type="number" class="small-text" value="<?php echo esc_attr($owner_id); ?>" /></td>
            </tr>
            <tr>
                <th><label for="renewal_of_id"><?php _e('Renewal of Credential ID', 'tmt-crm'); ?></label></th>
                <td><input name="renewal_of_id" id="renewal_of_id" type="number" class="small-text" value="<?php echo esc_attr($renewal_of_id); ?>" /></td>
            </tr>
            <tr>
                <th><label for="username"><?php _e('Username/Email', 'tmt-crm'); ?></label></th>
                <td><input name="username" id="username" type="text" class="regular-text" value="<?php echo esc_attr($username); ?>" /></td>
            </tr>
            <tr>
                <th><label for="secret_primary"><?php _e('Secret (Key/Password)', 'tmt-crm'); ?></label></th>
                <td><input name="secret_primary" id="secret_primary" type="text" class="regular-text" value="" /></td>
            </tr>
            <tr>
                <th><label for="secret_secondary"><?php _e('Secret (Secondary)', 'tmt-crm'); ?></label></th>
                <td><input name="secret_secondary" id="secret_secondary" type="text" class="regular-text" value="" /></td>
            </tr>
            <tr>
                <th><label for="extra_json"><?php _e('Extra JSON (encrypted)', 'tmt-crm'); ?></label></th>
                <td><textarea name="extra_json" id="extra_json" class="large-text code" rows="5"><?php echo esc_textarea($extra_json); ?></textarea></td>
            </tr>
        </tbody>
    </table>

    <?php submit_button($id ? __('Update License', 'tmt-crm') : __('Create License', 'tmt-crm')); ?>
</form>