<?php
defined('ABSPATH') || exit;

/** Biến từ CustomerScreen::render_edit() */
$customer = $customer ?? null;
$message  = $message  ?? '';
$action   = admin_url('admin-post.php');
$is_edit  = !empty($customer?->id);
?>
<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo $is_edit ? esc_html__('Chỉnh sửa khách hàng', 'tmt-customer-crm') : esc_html__('Thêm khách hàng mới', 'tmt-customer-crm'); ?>
    </h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=tmt-crm-customers')); ?>" class="page-title-action">
        <?php esc_html_e('Về danh sách', 'tmt-customer-crm'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if ($message === 'created'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Đã tạo khách hàng.', 'tmt-customer-crm'); ?></p>
        </div>
    <?php elseif ($message === 'updated'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Đã cập nhật khách hàng.', 'tmt-customer-crm'); ?></p>
        </div>
    <?php elseif ($message === 'error' && !empty($_GET['error'])): ?>
        <div class="notice notice-error">
            <p><?php echo esc_html(wp_unslash($_GET['error'])); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url($action); ?>">
        <?php wp_nonce_field('tmt_crm_save_customer', 'tmt_crm_customer_nonce'); ?>
        <input type="hidden" name="action" value="tmt_crm_save_customer">
        <input type="hidden" name="customer_id" value="<?php echo $is_edit ? (int)$customer->id : 0; ?>">

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="customer_name"><?php esc_html_e('Tên khách hàng', 'tmt-customer-crm'); ?></label></th>
                    <td><input name="customer_name" type="text" id="customer_name" value="<?php echo esc_attr($customer->name ?? ''); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="customer_email"><?php esc_html_e('Email', 'tmt-customer-crm'); ?></label></th>
                    <td><input name="customer_email" type="email" id="customer_email" value="<?php echo esc_attr($customer->email ?? ''); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="customer_phone"><?php esc_html_e('Điện thoại', 'tmt-customer-crm'); ?></label></th>
                    <td><input name="customer_phone" type="text" id="customer_phone" value="<?php echo esc_attr($customer->phone ?? ''); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="customer_company"><?php esc_html_e('Công ty', 'tmt-customer-crm'); ?></label></th>
                    <td><input name="customer_company" type="text" id="customer_company" value="<?php echo esc_attr($customer->company ?? ''); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="customer_address"><?php esc_html_e('Địa chỉ', 'tmt-customer-crm'); ?></label></th>
                    <td><input name="customer_address" type="text" id="customer_address" value="<?php echo esc_attr($customer->address ?? ''); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="customer_type"><?php esc_html_e('Loại khách hàng', 'tmt-customer-crm'); ?></label></th>
                    <td>
                        <select name="customer_type" id="customer_type">
                            <option value=""><?php esc_html_e('— Chọn —', 'tmt-customer-crm'); ?></option>
                            <option value="individual" <?php selected(($customer->type ?? ''), 'individual'); ?>><?php esc_html_e('Cá nhân', 'tmt-customer-crm'); ?></option>
                            <option value="company" <?php selected(($customer->type ?? ''), 'company'); ?>><?php esc_html_e('Công ty', 'tmt-customer-crm'); ?></option>
                            <option value="partner" <?php selected(($customer->type ?? ''), 'partner'); ?>><?php esc_html_e('Đối tác', 'tmt-customer-crm'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="customer_owner_id"><?php esc_html_e('Người phụ trách (User ID)', 'tmt-customer-crm'); ?></label></th>
                    <td><input name="customer_owner_id" type="number" id="customer_owner_id" min="0" value="<?php echo esc_attr($customer->owner_id ?? ''); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="customer_note"><?php esc_html_e('Ghi chú', 'tmt-customer-crm'); ?></label></th>
                    <td><textarea name="customer_note" id="customer_note" rows="5" class="large-text"><?php echo esc_textarea($customer->note ?? ''); ?></textarea></td>
                </tr>
            </tbody>
        </table>

        <?php submit_button($is_edit ? __('Cập nhật khách hàng', 'tmt-customer-crm') : __('Thêm khách hàng', 'tmt-customer-crm')); ?>
    </form>
</div>