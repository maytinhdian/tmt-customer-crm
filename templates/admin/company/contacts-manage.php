<?php

/** @var array $company */
/** @var \TMT\CRM\Presentation\Admin\CompanyContactsListTable $table */

use TMT\CRM\Presentation\Admin\Screen\CompanyScreen;
use TMT\CRM\Domain\ValueObject\CompanyContactRole;
use TMT\CRM\Presentation\Support\View;

$back_url = admin_url('admin.php?page=' . CompanyScreen::PAGE_SLUG);
$company_id   = isset($company_id) ? (int) $company_id : 0;
$company_name = isset($company_name) ? (string)($company_name) : '';
?>
<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html__('Quản lý liên hệ: ', 'tmt-crm') . esc_html($company_name ?? ('#' . $company_id)); ?>
    </h1>
    <a href="<?php echo esc_url($back_url); ?>" class="page-title-action">
        <?php esc_html_e('Quay lại danh sách công ty', 'tmt-crm'); ?>
    </a>
    <hr class="wp-header-end" />

    <div class="tmt-grid">
        <div>
            <h2><?php esc_html_e('Liên hệ đang active', 'tmt-crm'); ?></h2>
            <?php
            $table->display(); // Bảng bên trái
            ?>
        </div>

        <div>
            <h2><?php esc_html_e('Thêm liên hệ', 'tmt-crm'); ?></h2>

            <?php
            /**
             * Bạn đã upload một box form (CompanyContactsBox.php).
             * Nếu box này là partial, chỉ việc include vào đây để tái dùng UI/logic.
             * Box cần chấp nhận prefill company_id.
             */
            // $company_id = (int)$company['id'];
            $prefill_company_id = $company_id;

            // ✅ Partial: templates/admin/company/partials/add-contact-form.php
            View::render_admin_partial('company', 'add-contact-form', [
                'company_id' => (int)$company_id,
                'roles'      => CompanyContactRole::all(), // ví dụ gọi danh sách role
            ]);
            ?>

            <!-- Nếu muốn form gọn tại chỗ (không dùng partial), tham khảo khung dưới: -->
            <?php if (false): ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php?action=tmt_crm_company_contact_attach')); ?>">
                    <?php wp_nonce_field('tmt_crm_company_contact_attach_' . $company_id); ?>
                    <input type="hidden" name="company_id" value="<?php echo esc_attr($company_id); ?>" />

                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th><label for="customer_id"><?php _e('Khách hàng', 'tmt-crm'); ?></label></th>
                                <td>
                                    <select id="customer_id" name="customer_id" style="width:100%"
                                        data-ajax-action="tmt_crm_search_customers"
                                        data-placeholder="<?php esc_attr_e('Chọn khách hàng…', 'tmt-crm'); ?>"></select>
                                    <p class="description"><?php _e('Chọn khách hàng đã có để gán vào công ty', 'tmt-crm'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="role"><?php _e('Vai trò', 'tmt-crm'); ?></label></th>
                                <td><input type="text" id="role" name="role" class="regular-text" /></td>

                            </tr>
                            <tr>
                                <th><label for="position"><?php _e('Chức vụ', 'tmt-crm'); ?></label></th>
                                <td><input type="text" id="position" name="position" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><label for="is_primary"><?php _e('Liên hệ chính', 'tmt-crm'); ?></label></th>
                                <td><label><input type="checkbox" id="is_primary" name="is_primary" value="1" /> <?php _e('Đặt làm chính', 'tmt-crm'); ?></label></td>
                            </tr>
                            <tr>
                                <th><label for="start_date"><?php _e('Bắt đầu', 'tmt-crm'); ?></label></th>
                                <td><input type="date" id="start_date" name="start_date" /></td>
                            </tr>
                        </tbody>
                    </table>

                    <?php submit_button(__('Gán vào công ty', 'tmt-crm')); ?>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>