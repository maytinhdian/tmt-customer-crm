<?php

/**
 * @var \TMT\CRM\Application\DTO\CompanyDTO|null $company
 */
defined('ABSPATH') || exit;

use TMT\CRM\Presentation\Admin\CompanyScreen;
use TMT\CRM\Infrastructure\Security\Capability;
use TMT\CRM\Presentation\Admin\Company\Form\CompanyContactsBox;

$is_edit   = $company && !empty($company->id);
$form_url  = admin_url('admin-post.php');
$back_url  = add_query_arg(['page' => CompanyScreen::PAGE_SLUG], admin_url('admin.php'));
$nonce_key = $is_edit ? ('tmt_crm_company_update_' . (int) $company->id) : 'tmt_crm_company_create';

$title = $is_edit ? __('Sửa công ty', 'tmt-crm') : __('Thêm công ty', 'tmt-crm');
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($title); ?></h1>
    <a href="<?php echo esc_url($back_url); ?>" class="page-title-action"><?php esc_html_e('Quay lại', 'tmt-crm'); ?></a>
    <hr class="wp-header-end" />

    <form action="<?php echo esc_url($form_url); ?>" method="post" class="tmt-crm-company-form">
        <input type="hidden" name="action" value="<?php echo esc_attr(CompanyScreen::ACTION_SAVE); ?>">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?php echo (int) $company->id; ?>">
        <?php endif; ?>
        <?php wp_nonce_field($nonce_key); ?>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="tmt-company-name"><?php esc_html_e('Tên công ty', 'tmt-crm'); ?> <span class="description" style="color: red;">*</span></label></th>
                    <td>
                        <input name="name" type="text" id="tmt-company-name" class="regular-text" required
                            value="<?php echo isset($company->name) ? esc_attr($company->name) : ''; ?>">
                        <p class="description"><?php esc_html_e('Bắt buộc.', 'tmt-crm'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="tmt-company-tax-code"><?php esc_html_e('Mã số thuế', 'tmt-crm'); ?></label></th>
                    <td>
                        <input name="tax_code" type="text" id="tmt-company-tax-code" class="regular-text"
                            value="<?php echo isset($company->tax_code) ? esc_attr($company->tax_code) : ''; ?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="tmt-company-email"><?php esc_html_e('Email', 'tmt-crm'); ?></label></th>
                    <td>
                        <input name="email" type="email" id="tmt-company-email" class="regular-text"
                            value="<?php echo isset($company->email) ? esc_attr($company->email) : ''; ?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="tmt-company-phone"><?php esc_html_e('Điện thoại', 'tmt-crm'); ?></label></th>
                    <td>
                        <input name="phone" type="text" id="tmt-company-phone" class="regular-text"
                            value="<?php echo isset($company->phone) ? esc_attr($company->phone) : ''; ?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="tmt-company-address"><?php esc_html_e('Địa chỉ', 'tmt-crm'); ?></label></th>
                    <td>
                        <input name="address" type="text" id="tmt-company-address" class="regular-text"
                            value="<?php echo isset($company->address) ? esc_attr($company->address) : ''; ?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="tmt-company-representer"><?php esc_html_e('Người đại diện', 'tmt-crm'); ?></label></th>
                    <td>
                        <input name="representer" type="text" id="tmt-company-representer" class="regular-text"
                            value="<?php echo isset($company->representer) ? esc_attr($company->representer) : ''; ?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="tmt-company-website"><?php esc_html_e('Website', 'tmt-crm'); ?></label></th>
                    <td>
                        <input name="website" type="url" id="tmt-company-website" class="regular-text"
                            value="<?php echo isset($company->website) ? esc_attr($company->website) : ''; ?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="tmt-company-note"><?php esc_html_e('Ghi chú', 'tmt-crm'); ?></label></th>
                    <td>
                        <textarea name="note" id="tmt-company-note" class="large-text code" rows="4">
                            <?php
                            echo isset($company->note) ? esc_textarea($company->note) : '';
                            ?>
                        </textarea>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="tmt-company-owner"><?php esc_html_e('Người phụ trách', 'tmt-crm'); ?></label></th>
                    <td>
                        <?php
                        // Dropdown user (có thể filter role theo nhu cầu)
                        wp_dropdown_users([
                            'name'              => 'owner_id',
                            'id'                => 'tmt-company-owner',
                            'selected'          => isset($company->owner_id) ? (int) $company->owner_id : 0,
                            'show_option_none'  => __('— Không chọn —', 'tmt-crm'),
                            'option_none_value' => 0,
                        ]);
                        // Chỉ hiển thị box vai trò khi đã có company_id (trang Edit).
                        if (!empty($company?->id)) {
                            \TMT\CRM\Presentation\Admin\Company\Form\CompanyRolesBox::render((int)$company->id);
                        } else {
                            echo '<p class="description">Lưu công ty trước, sau đó bạn có thể gán liên hệ theo vai trò.</p>';
                        }

                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?
        $company_id = $id ?? 0;
        CompanyContactsBox::render((int)$company_id);
        ?>
        <?php if ($is_edit ? current_user_can(Capability::COMPANY_UPDATE) : current_user_can(Capability::COMPANY_UPDATE)) : ?>
            <?php submit_button($is_edit ? __('Cập nhật', 'tmt-crm') : __('Tạo mới', 'tmt-crm')); ?>
        <?php endif; ?>

        <?php if ($is_edit && current_user_can(Capability::COMPANY_DELETE)) : ?>
            <?php
            $del_url = wp_nonce_url(
                add_query_arg([
                    'action' => CompanyScreen::ACTION_DELETE,
                    'id'     => (int) $company->id,
                ], admin_url('admin-post.php')),
                'tmt_crm_company_delete_' . (int) $company->id
            );
            ?>
            <a class="button button-link-delete" href="<?php echo esc_url($del_url); ?>" onclick="return confirm('<?php echo esc_attr__('Xoá công ty này?', 'tmt-crm'); ?>');">
                <?php esc_html_e('Xoá', 'tmt-crm'); ?>
            </a>
        <?php endif; ?>
    </form>
</div>