<?php

declare(strict_types=1);

use TMT\CRM\Shared\Presentation\Support\View;

/** render form add/edit; không hiển plain password sau khi lưu */

?>
<div class="tmt-password-form">
    <h2><?php esc_html_e('Thêm / Sửa Password', 'tmt-crm'); ?></h2>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="tmt_crm_password_save">
        <?php wp_nonce_field('tmt_crm_password_save'); ?>
        <!-- id (nếu edit) -->
        <?php if (!empty($id)) : ?>
            <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
        <?php endif; ?>
        <table class="form-table">
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

                    <div id="tmt-picker-company" class="tmt-subject-picker" style="margin-top:8px; width: 25%;">
                        <label for="company_id" style="display: none;"><strong><?php esc_html_e('Công ty', 'tmt-crm'); ?></strong></label>
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
                        <p class="description"><?php esc_html_e('Chọn công ty (có thể dùng Select2 AJAX).', 'tmt-crm'); ?></p>
                    </div>

                    <div id="tmt-picker-customer" class="tmt-subject-picker" style="margin-top:8px; width: 25%;">
                        <label for="customer_id" style="display:none;"><?php esc_html_e('Khách hàng', 'tmt-crm'); ?></label>
                        <select id="customer_id" name="customer_id" style="min-width:320px;">
                            <?php if ($customer_id > 0): ?>
                                <option value="<?php echo (int)$customer_id; ?>" selected><?php echo esc_html(get_post_meta($customer_id, '_customer_name', true) ?: 'ID #' . $customer_id); ?></option>
                            <?php endif; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Chọn khách hàng (có thể dùng Select2 AJAX).', 'tmt-crm'); ?></p>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label for="tmt-category"><?php esc_html_e('Phân loại', 'tmt-crm'); ?></label></th>
                <td>
                    <select id="tmt-category" name="category">
                        <option value=""><?php esc_html_e('— Chọn —', 'tmt-crm'); ?></option>
                        <option value="email">Email</option>
                        <option value="hosting">Hosting</option>
                        <option value="wifi">Wi-Fi</option>
                        <option value="server">Server</option>
                        <option value="erp">ERP</option>
                        <!-- tuỳ bạn thêm -->
                    </select>
                </td>
            </tr>

            <tr>
                <th>Tiêu đề</th>
                <td><input type="text" name="title" class="regular-text" required></td>
            </tr>
            <tr>
                <th>Tài khoản</th>
                <td><input type="text" name="username" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="tmt-password"><?php esc_html_e('Mật khẩu', 'tmt-crm'); ?></label></th>
                <td>
                    <div class="tmt-password-field">
                        <input
                            type="password"
                            id="tmt-password"
                            name="password"
                            class="regular-text"
                            autocomplete="new-password" />
                        <button
                            type="button"
                            class="tmt-password-toggle"
                            aria-label="<?php esc_attr_e('Hiện mật khẩu', 'tmt-crm'); ?>"
                            data-target="tmt-password">
                            <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                        </button>
                    </div>
                    <p class="description"><?php esc_html_e('Nhấn biểu tượng để hiện/ẩn nội dung.', 'tmt-crm'); ?></p>
                </td>
            </tr>

            <tr>
                <th>URL</th>
                <td><input type="url" name="url" class="regular-text"></td>
            </tr>
            <tr>
                <th>Ghi chú</th>
                <td><textarea name="notes" rows="4" class="large-text"></textarea></td>
            </tr>
        </table>
        <?php submit_button(__('Lưu', 'tmt-crm')); ?>
    </form>
</div>
<script>
    (function() {
        function updateSubjectUI(value) {
            var companyBox = document.getElementById('tmt-picker-company');
            var customerBox = document.getElementById('tmt-picker-customer');
            var companySel = document.getElementById('tmt-company-id');
            var customerSel = document.getElementById('tmt-customer-id');

            var isCompany = value === 'company';
            var isCustomer = value === 'customer';

            // Hiện/ẩn UI
            companyBox.style.display = isCompany ? '' : 'none';
            customerBox.style.display = isCustomer ? '' : 'none';

            // Bật/tắt trường submit
            if (companySel) {
                companySel.disabled = !isCompany;
                companySel.required = isCompany;
                if (!isCompany) {
                    // clear khi chuyển qua loại khác
                    if (companySel.tagName === 'SELECT') {
                        companySel.value = '';
                        if (window.jQuery && jQuery.fn && jQuery.fn.select2) jQuery(companySel).val(null).trigger('change');
                    }
                }
            }
            if (customerSel) {
                customerSel.disabled = !isCustomer;
                customerSel.required = isCustomer;
                if (!isCustomer) {
                    if (customerSel.tagName === 'SELECT') {
                        customerSel.value = '';
                        if (window.jQuery && jQuery.fn && jQuery.fn.select2) jQuery(customerSel).val(null).trigger('change');
                    }
                }
            }
        }

        document.addEventListener('change', function(e) {
            if (e.target && e.target.name === 'subject') {
                updateSubjectUI(e.target.value);
            }
        });

        // Khởi tạo theo giá trị hiện tại (mặc định company)
        document.addEventListener('DOMContentLoaded', function() {
            var checked = document.querySelector('input[name="subject"]:checked');
            updateSubjectUI(checked ? checked.value : 'company');
        });
    })();
</script>