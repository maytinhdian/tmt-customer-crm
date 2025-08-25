<?php

/**
 * Template: Form thêm/sửa khách hàng (Edit View)
 * Biến truyền vào:
 * - $customer (CustomerDTO|null)
 */
defined('ABSPATH') || exit;

$is_edit   = $customer && !empty($customer->id);
$id        = $is_edit ? (int)$customer->id : 0;
$title_txt = $is_edit ? __('Chỉnh sửa khách hàng', 'tmt-crm') : __('Thêm khách hàng mới', 'tmt-crm');
$nonce     = $is_edit ? 'tmt_crm_customer_update_' . $id : 'tmt_crm_customer_create';

$owner_choices     = isset($owner_choices) && is_array($owner_choices) ? $owner_choices : [];
$owner_id_selected = isset($owner_id_selected) ? (int)$owner_id_selected : 0;

// Helper lấy field an toàn
$val = function ($prop, $default = '') use ($customer) {
    if (!$customer) return $default;
    return esc_attr($customer->{$prop} ?? $default);
};
?>
<div class="wrap">
    <h1><?php echo esc_html($title_txt); ?></h1>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field($nonce); ?>
        <input type="hidden" name="action" value="tmt_crm_customer_save" />
        <input type="hidden" name="id" value="<?php echo (int)$id; ?>" />

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th><label for="name"><?php _e('Tên khách hàng', 'tmt-crm'); ?></label></th>
                    <td><input type="text" id="name" name="name" class="regular-text" value="<?php echo $val('name'); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="email"><?php _e('Email', 'tmt-crm'); ?></label></th>
                    <td><input type="email" id="email" name="email" class="regular-text" value="<?php echo $val('email'); ?>"></td>
                </tr>
                <tr>
                    <th><label for="phone"><?php _e('Số điện thoại', 'tmt-crm'); ?></label></th>
                    <td><input type="text" id="phone" name="phone" class="regular-text" value="<?php echo $val('phone'); ?>"></td>
                </tr>

                <tr>
                    <th><label for="address"><?php _e('Địa chỉ', 'tmt-crm'); ?></label></th>
                    <td><input type="text" id="address" name="address" class="regular-text" value="<?php echo esc_attr($customer->address ?? ''); ?>"></td>
                </tr>
                <!---------------Select2 Demo---------------------->
                <tr>
                    <th scope="row"><label for="company_id"><?php _e('Công ty', 'tmt-crm'); ?></label></th>
                    <td>
                        <select id="company_id" name="company_id" class="regular-text"
                            data-initial-id="<?php echo esc_attr((string)($company_id_selected ?? 0)); ?>">
                            <?php if (!empty($company_id_selected)): ?>
                                <!-- Không cần option tĩnh nếu dùng ensureInitialValue(); để rỗng -->
                            <?php endif; ?>
                        </select>
                        <p class="description"><?php _e('Gõ để tìm công ty.', 'tmt-crm'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="owner_id"><?php _e('Người phụ trách', 'tmt-crm'); ?></label></th>
                    <td>
                        <select id="owner_id" name="owner_id" class="regular-text"
                            data-initial-id="<?php echo esc_attr((string)($owner_id_selected ?? 0)); ?>">
                        </select>
                        <p class="description"><?php _e('Gõ để tìm nhân viên.', 'tmt-crm'); ?></p>
                    </td>
                </tr>

                <!-- Người phụ trách: hiển thị tên, lưu ID -->
                <tr>
                    <th scope="row"><label for="owner_id"><?php _e('Người phụ trách', 'tmt-crm'); ?></label></th>
                    <td>
                        <select id="owner_id" name="owner_id" class="regular-text">
                            <option value=""><?php echo esc_html__('— Chọn nhân viên phụ trách —', 'tmt-crm'); ?></option>
                            <?php foreach ($owner_choices as $uid => $label): ?>
                                <option value="<?php echo esc_attr((string)$uid); ?>"
                                    <?php selected($owner_id_selected, $uid); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Label hiển thị tên, DB lưu ID.', 'tmt-crm'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="note"><?php _e('Ghi chú', 'tmt-crm'); ?></label></th>
                    <td><textarea id="note" name="note" rows="4" class="large-text"><?php echo esc_textarea($customer->note ?? ''); ?></textarea></td>
                </tr>
            </tbody>
        </table>

        <?php submit_button($is_edit ? __('Cập nhật', 'tmt-crm') : __('Tạo khách hàng', 'tmt-crm')); ?>
    </form>
</div>