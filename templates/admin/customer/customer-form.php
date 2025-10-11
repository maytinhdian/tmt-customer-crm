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

// Helper lấy field an toàn
$val = function ($prop, $default = '') use ($customer) {
    if (!$customer) return $default;
    return esc_attr($customer->{$prop} ?? $default);
};
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($title_txt); ?></h1>
    <a href="<?php echo esc_url($back_url); ?>" class="page-title-action"><?php esc_html_e('Quay lại danh sách chính', 'tmt-crm'); ?></a>
    <hr class="wp-header-end" />

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

                <!-- Người phụ trách: hiển thị tên, lưu ID -->
                <?php
                /** @var \TMT\CRM\Application\DTO\CustomerDTO|null $customer */
                $owner_id_selected = (int) ($customer->owner_id ?? 0); // ID người phụ trách đã lưu
                ?>
                <tr>
                    <th scope="row"><label for="owner_id"><?php _e('Người phụ trách', 'tmt-crm'); ?></label></th>
                    <td>
                        <select id="owner_id" name="owner_id" class="regular-text"
                            data-initial-id="<?php echo esc_attr((string)$owner_id_selected); ?>">
                            <option value=""></option>
                        </select>
                        <p class="description"><?php _e('Gõ để tìm người dùng.', 'tmt-crm'); ?></p>
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