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
            <tr>
                <th><?php esc_html_e('Đối tượng', 'tmt-crm'); ?></th>
                <td>
                    <label><input type="radio" name="subject" value="company" checked> <?php esc_html_e('Công ty', 'tmt-crm'); ?></label>
                    &nbsp;&nbsp;
                    <label><input type="radio" name="subject" value="customer"> <?php esc_html_e('Khách hàng', 'tmt-crm'); ?></label>

                    <div class="tmt-subject-picker" data-for="company" style="margin-top:8px;">
                        <label><?php esc_html_e('Chọn công ty', 'tmt-crm'); ?></label>
                        <input type="number" name="company_id" min="1" class="small-text" />
                        <!-- hoặc Select2 ajax -->
                    </div>

                    <div class="tmt-subject-picker" data-for="customer" style="margin-top:8px; display:none;">
                        <label><?php esc_html_e('Chọn khách hàng', 'tmt-crm'); ?></label>
                        <input type="number" name="customer_id" min="1" class="small-text" />
                        <!-- hoặc Select2 ajax -->
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