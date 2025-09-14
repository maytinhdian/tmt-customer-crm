<?php

/** @var array $company */
/** @var \TMT\CRM\Presentation\Admin\CompanyContactsListTable $table */

use TMT\CRM\Modules\Company\Presentation\Admin\Screen\CompanyScreen;
use TMT\CRM\Modules\Contact\Domain\ValueObject\CompanyContactRole;
use TMT\CRM\Presentation\Support\View;

$back_url = admin_url('admin.php?page=' . CompanyScreen::PAGE_SLUG);
$company_id   = isset($company_id) ? (int) $company_id : 0;
$contact_id = isset($contact_id) ? (int)$contact_id : 0;
$company_name = isset($company_name) ? (string)($company_name) : '';
$edit_contact = isset($edit_contact) ? $edit_contact : '';
$editing     = (isset($_GET['action']) && $_GET['action'] === 'edit');

// error_log('[TMT CRM] $_GET["action"]: ' . $editing);
// error_log('[TMT CRM] company_id: ' . $company_id);
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
            <h2><?php 
            // esc_html_e('Thêm liên hệ', 'tmt-crm'); 
            ?></h2>
            <?php
            // ✅ Partial: templates/admin/company/partials/contact-form.php
            View::render_admin_partial('company', 'contact-form', [
                'company_id' => (int)$company_id,
                'contact_id' => (int)$contact_id,
                'editing' => (int)$editing,
                'edit_contact' => $edit_contact,
                // 'customer_label' => $customer_label,
                'roles'      => CompanyContactRole::all(), // ví dụ gọi danh sách role
            ]);
            ?>
        </div>
    </div>
</div>