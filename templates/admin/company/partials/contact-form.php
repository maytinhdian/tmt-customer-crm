<?php

/**
 * Partial: Add/Update Company Contact
 *
 * Expect:
 * - $company_id   (int, required)
 * - $editing      (bool)
 * - $contact_id   (int)
 * - $edit_contact (CompanyContactDTO|null) // prefill khi sửa
 */

use TMT\CRM\Modules\Contact\Domain\ValueObject\CompanyContactRole;

$company_id   = isset($company_id) ? (int) $company_id : 0;
$editing      = isset($editing) ? (bool) $editing : false;
$contact_id   = isset($contact_id) ? (int) $contact_id : 0;  // 👈 THÊM DÒNG NÀY
$edit_contact = isset($edit_contact)  ? $edit_contact : null;

// Đầu partial
if (!isset($edit_contact)) {
    error_log('[CRM] Partial got edit_contact: ' . (is_object($edit_contact) ? get_class($edit_contact) : gettype($edit_contact)));
}

// Prefill
$prefill = [
    'contact_id' => $edit_contact?->id          ?? 0,
    'customer_id' => $edit_contact->customer_id ?? 0,
    'role'       => $edit_contact->role       ?? '',
    'title'      => $edit_contact->title      ?? '',
    'is_primary' => !empty($edit_contact?->is_primary),
    'start_date' => $edit_contact->start_date ?? '',
    'end_date'   => $edit_contact->end_date   ?? '',
];

// Labels for roles
$role_options = method_exists(CompanyContactRole::class, 'labels')
    ? CompanyContactRole::labels()
    : array_combine(CompanyContactRole::all(), CompanyContactRole::all()); // fallback simple

// Post routing
$action   = $editing ? 'tmt_crm_company_contact_update' : 'tmt_crm_company_contact_attach';
$nonce_field   = $editing ? 'tmt_crm_company_contact_update_'  : 'tmt_crm_company_contact_attach_';
$btn_text = $editing ? __('Cập nhật', 'tmt-crm') : __('Gán vào công ty', 'tmt-crm');
?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="contact-form" class="tmt-contact-form">
    <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>">
    <input type="hidden" name="company_id" value="<?php echo (int) $company_id; ?>">
    <!-- Nonce field -->
    <?php wp_nonce_field($nonce_field); ?>

    <!-- Nếu view=edit -->
    <?php if ($editing && $prefill['contact_id'] > 0): ?>
        <input type="hidden" name="contact_id" value="<?php echo (int) $prefill['contact_id']; ?>">
    <?php endif; ?>

    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th><label for="customer_id"><?php _e('Khách hàng', 'tmt-crm'); ?></label></th>
                <td>
                    <?php if ($editing && ($prefill['contact_id'] ?? 0) > 0): ?>
                        <!-- Đang sửa: không cho đổi khách hàng -->
                        <input type="hidden" name="customer_id" value="<?php echo $prefill['customer_id']; ?>">
                        <input type="text" class="regular-text" value="<?php echo esc_attr($customer_label); ?>" readonly disabled>
                        <p class="description">
                            <?php _e('Không thể đổi khách hàng khi sửa liên hệ. Muốn đổi, hãy gỡ liên hệ và tạo lại.', 'tmt-crm'); ?>
                        </p>
                    <?php else: ?>
                        <!-- Thêm mới: chọn khách hàng bằng Select2 (AJAX) -->
                        <select id="customer_id" name="customer_id" style="width: 420px"
                            data-ajax-action="tmt_crm_search_customers"
                            data-placeholder="<?php esc_attr_e('Chọn khách hàng…', 'tmt-crm'); ?>">
                        </select>
                        <p class="description">
                            <?php _e('Chọn khách hàng đã có để gán vào công ty', 'tmt-crm'); ?>
                        </p>
                    <?php endif; ?>


                </td>
            </tr>
            <tr>
                <th><label for="role"><?php _e('Vai trò', 'tmt-crm'); ?></label></th>
                <td>
                    <select id="role" name="role" required>
                        <?php if (empty($prefill['role'])): ?>
                            <option value="" disabled selected><?php echo esc_html__('— Chọn vai trò —', 'tmt-crm'); ?></option>
                        <?php endif; ?>
                        <?php foreach ($role_options as $value => $text): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($value, $prefill['role']); ?>>
                                <?php echo esc_html($text); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label for="title"><?php _e('Chức danh (tuỳ chọn)', 'tmt-crm'); ?></label></th>
                <td>
                    <input type="text" id="title" name="title" class="regular-text"
                        value="<?php echo esc_attr($prefill['title']); ?>">
                </td>
            </tr>

            <tr>
                <th><?php _e('Liên hệ chính', 'tmt-crm'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="is_primary" value="1" <?php checked(true, $prefill['is_primary']); ?>>
                        <?php _e('Đặt làm liên hệ chính của công ty', 'tmt-crm'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th><label for="start_date"><?php _e('Bắt đầu', 'tmt-crm'); ?></label></th>
                <td>
                    <input type="date" id="start_date" name="start_date"
                        value="<?php echo esc_attr($prefill['start_date']); ?>">
                </td>
            </tr>

            <?php if ($editing): ?>
                <tr class="form-field">
                    <th scope="row">
                        <label for="end_date"><?php _e('Ngày kết thúc', 'tmt-crm'); ?></label>
                    </th>
                    <td>
                        <input type="date" id="end_date" name="end_date"
                            value="<?php echo esc_attr($prefill['end_date']); ?>">
                        <p class="description"><?php _e('Chỉ nhập khi liên hệ này ngừng hiệu lực.', 'tmt-crm'); ?></p>
                    </td>
                </tr>
            <?php else: ?>
                <!-- Thêm mới: không hiển thị; nếu cần tránh notice ở handler, có thể gửi hidden rỗng -->
                <input type="hidden" name="end_date" value="">
            <?php endif; ?>
        </tbody>
    </table>

    <p class="submit">
        <?php submit_button($btn_text, 'primary', 'submit', false); ?>
        <?php
        // Link huỷ sửa: bỏ view/contact_id khỏi URL hiện tại
        if ($editing) :
            $cancel_url = remove_query_arg(['view', 'contact_id', 'action']);
        ?>
            <a class="button button-secondary" href="<?php echo esc_url($cancel_url); ?>">
                <?php _e('Hủy sửa', 'tmt-crm'); ?>
            </a>
        <?php endif; ?>
    </p>
</form>