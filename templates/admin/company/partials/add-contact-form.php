<?php

/**
 * Template: Form gán liên hệ vào công ty
 * Biến yêu cầu: $company_id (int)
 *
 * Lưu ý: file này ở ngoài `src/` để tránh PSR-4. Không namespace.
 */

/** Chắn nhầm biến */
$company_id = isset($company_id) ? (int)$company_id : 0;

use TMT\CRM\Infrastructure\Security\Capability;


if ($company_id <= 0) {
    echo '<div class="notice notice-error"><p>'
        . esc_html__('Thiếu company_id.', 'tmt-crm')
        . '</p></div>';
    return;
}

/** Nếu không có quyền cập nhật công ty → chỉ thông báo (không die) */
if (!current_user_can(Capability::COMPANY_UPDATE)) {
    echo '<p class="description" style="opacity:.8">'
        . esc_html__('Bạn không có quyền thêm liên hệ cho công ty này.', 'tmt-crm')
        . '</p>';
    return;
}

$action_url = admin_url('admin-post.php');
?>
<form method="post" action="<?php echo esc_url($action_url); ?>" class="tmt-form tmt-add-contact">
    <input type="hidden" name="action" value="tmt_crm_company_contact_attach" />
    <?php wp_nonce_field('tmt_crm_company_contact_attach_' . $company_id); ?>
    <input type="hidden" name="company_id" value="<?php echo esc_attr($company_id); ?>" />

    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th><label for="customer_id"><?php _e('Khách hàng', 'tmt-crm'); ?></label></th>
                <td>
                    <select id="customer_id" name="customer_id" style="width: 420px"
                        data-ajax-action="tmt_crm_search_customers"
                        data-placeholder="<?php esc_attr_e('Chọn khách hàng…', 'tmt-crm'); ?>">
                    </select>
                    <p class="description"><?php _e('Chọn khách hàng đã có để gán vào công ty', 'tmt-crm'); ?></p>
                </td>
            </tr>

            <tr>
                <th><label for="role"><?php _e('Vai trò', 'tmt-crm'); ?></label></th>
                <td><select name="role" required>
                        <?php foreach ($roles as $key => $label): ?>
                            <option value="<?php echo esc_attr($label); ?>">
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label for="title"><?php _e('Chức vụ', 'tmt-crm'); ?></label></th>
                <td><input type="text" id="title" name="title" class="regular-text" /></td>

            </tr>

            <tr>
                <th><label for="is_primary"><?php _e('Liên hệ chính', 'tmt-crm'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="is_primary" name="is_primary" value="1" />
                        <?php _e('Đặt làm chính', 'tmt-crm'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th><label for="start_date"><?php _e('Bắt đầu', 'tmt-crm'); ?></label></th>
                <td><input type="date" id="start_date" name="start_date" /></td>
            </tr>
        </tbody>
    </table>

    <?php submit_button(__('Gán vào công ty', 'tmt-crm')); ?>
</form>