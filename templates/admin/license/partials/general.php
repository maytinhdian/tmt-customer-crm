<?php

/**
 * @var string $action
 * @var array  $general
 */
defined('ABSPATH') || exit;

use TMT\CRM\Shared\Presentation\Support\View;

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
                <td><input name="label" id="label" type="text" class="regular-text"
                        value="<?php echo esc_attr($label); ?>" required /></td>
            </tr>
            <tr>
                <th><label for="type"><?php _e('Type', 'tmt-crm'); ?></label></th>
                <td>
                    <select name="type" id="type">
                        <?php foreach (['LICENSE_KEY', 'EMAIL_ACCOUNT', 'SAAS_ACCOUNT', 'API_TOKEN', 'WIFI_ACCOUNT', 'OTHER'] as $t): ?>
                            <option value="<?php echo esc_attr($t); ?>" <?php selected($type, $t); ?>>
                                <?php echo esc_html($t); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="status"><?php _e('Status', 'tmt-crm'); ?></label></th>
                <td>
                    <select name="status" id="status">
                        <?php foreach (['active', 'disabled', 'expired', 'revoked', 'pending'] as $st): ?>
                            <option value="<?php echo esc_attr($st); ?>" <?php selected($status, $st); ?>>
                                <?php echo esc_html($st); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="expires_at"><?php _e('Expires At', 'tmt-crm'); ?></label></th>
                <td><input name="expires_at" id="expires_at" type="text" class="regular-text" placeholder="(YYYY-MM-DD)"
                        value="<?php echo esc_attr($expires_at); ?>" />
                    <p class="description">
                        <?php _e('Để trống thì mặc định là 365 ngày kể từ ngày lưu.', 'tmt-crm'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="seats_total"><?php _e('Seats Total', 'tmt-crm'); ?></label></th>
                <td><input name="seats_total" id="seats_total" type="number" min="1" max="50" default="1"
                        class="small-text" value="<?php echo esc_attr($seats_total); ?>" /></td>
            </tr>
            <tr>
                <th><label for="sharing_mode"><?php _e('Sharing Mode', 'tmt-crm'); ?></label></th>
                <td>
                    <select name="sharing_mode" id="sharing_mode">
                        <?php foreach (['none', 'seat_allocation', 'family_share'] as $m): ?>
                            <option value="<?php echo esc_attr($m); ?>" <?php selected($sharing_mode, $m); ?>>
                                <?php echo esc_html($m); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php
            // Giá trị khi edit (nếu có)
            $subject     = isset($Q->subject) && in_array($Q->subject, ['company', 'customer'], true) ? $Q->subject : 'company';
            $company_id  = isset($Q->company_id) ? (int)$Q->company_id : 0;
            $customer_id = isset($Q->customer_id) ? (int)$Q->customer_id : 0;
            ?>
            <tr>
                <th><?php esc_html_e('Đối tượng', 'tmt-crm'); ?></th>
                <td>
                    <label style="margin-right:16px;">
                        <input type="radio" name="subject" value="company" <?php checked($subject, 'company'); ?> />
                        <?php esc_html_e('Công ty', 'tmt-crm'); ?>
                    </label>
                    <label>
                        <input type="radio" name="subject" value="customer" <?php checked($subject, 'customer'); ?> />
                        <?php esc_html_e('Khách hàng', 'tmt-crm'); ?>
                    </label>
                    <div id="tmt-customer-select" style="display: none;">
                        <label for="customer_id"
                            style="display:none;"><?php esc_html_e('Khách Hàng Cá Nhân', 'tmt-crm'); ?></label>
                        <select id="customer_id" name="customer_id" style="min-width:320px;" data-placeholder="Chọn khách hàng cá nhân">
                            <?php if ($customer_id > 0): ?>
                                <option value="<?php echo (int)$customer_id; ?>" selected>
                                    <?php echo esc_html(get_post_meta($customer_id, '_customer_name', true) ?: 'ID #' . $customer_id); ?>
                                </option>
                            <?php endif; ?>
                        </select>

                    </div>
                    <div id="tmt-company-select">
                        <label for="company_id"
                            style="display: none;"><strong><?php esc_html_e('Khách Hàng Doanh Nghiệp', 'tmt-crm'); ?></strong></label>

                        <select id="company_id" name="company_id" data-placeholder="Chọn công ty..."
                            data-ajax-action="tmt_crm_search_companies" data-initial-id="1">
                            <?php if (!empty($company_id)) : ?>
                                <option value="<?php echo (int)$company_id; ?>" selected>
                                    <?php echo esc_html($company_name ?? ('ID #' . $company_id)); ?>
                                </option>
                            <?php endif; ?>
                        </select>

                    </div>
                </td>
            </tr>
            <tr>
                <th><label for="owner_id"><?php _e('Owner ID', 'tmt-crm'); ?></label></th>
                <td><input name="owner_id" id="owner_id" type="number" class="small-text"
                        value="<?php echo esc_attr($owner_id); ?>" /></td>
            </tr>
            <tr>
                <th><label for="renewal_of_id"><?php _e('Renewal of Credential ID', 'tmt-crm'); ?></label></th>
                <td><input name="renewal_of_id" id="renewal_of_id" type="number" class="small-text"
                        value="<?php echo esc_attr($renewal_of_id); ?>" /></td>
            </tr>
            <tr>
                <th><label for="username"><?php _e('Username/Email', 'tmt-crm'); ?></label></th>
                <td><input name="username" id="username" type="text" class="regular-text"
                        value="<?php echo esc_attr($username); ?>" /></td>
            </tr>
            <tr>
                <th><label for="secret_primary"><?php _e('Secret (Key/Password)', 'tmt-crm'); ?></label></th>
                <td><input name="secret_primary" id="secret_primary" type="text" class="regular-text" value="" /></td>
            </tr>
            <tr>
                <th><label for="secret_secondary"><?php _e('Secret (Secondary)', 'tmt-crm'); ?></label></th>
                <td><input name="secret_secondary" id="secret_secondary" type="text" class="regular-text" value="" />
                </td>
            </tr>
            <tr>
                <th><label for="extra_json"><?php _e('Extra JSON (encrypted)', 'tmt-crm'); ?></label></th>
                <td><textarea name="extra_json" id="extra_json" class="large-text code"
                        rows="5"><?php echo esc_textarea($extra_json); ?></textarea></td>
            </tr>
        </tbody>
    </table>

    <?php submit_button($id ? __('Update License', 'tmt-crm') : __('Create License', 'tmt-crm'));

    ?>

</form>
<?php
if ($id <= 0) {
    echo '<p class="notice notice-warning"><em>Lưu license trước khi tải tệp.</em></p>';
    return;
}
View::render_admin_partial('core/files', 'attachments-panel', [
    'entity_type'  => 'license',
    'entity_id'    => (int)$id,
    'meta'         => ['tag' => 'license-card', 'license_code' => $label ?? ''],
    'allow_delete' => true, // nếu muốn hiện nút Xoá
    // 'files'      => $files, // optional: bạn có thể truyền sẵn để khỏi query lại
]);
