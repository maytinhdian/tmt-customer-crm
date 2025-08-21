<?php

namespace TMT\CRM\Presentation\Admin;


use TMT\CRM\Application\Services\CompanyService;
use TMT\CRM\Application\DTO\CompanyDTO ;

defined('ABSPATH') || exit;

/**
 * Màn hình chỉnh sửa thông tin khách hàng trong Admin
 */
class CustomerEditScreen
{
    private CompanyService $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    /**
     * Khởi động hook cho màn hình chỉnh sửa
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_post_tmt_crm_save_customer', [$this, 'handleSave']);
    }

    /**
     * Thêm menu "Khách hàng"
     */
    public function registerMenu(): void
    {
        add_submenu_page(
            'tmt-crm',
            __('Chỉnh sửa khách hàng', 'tmt-crm'),
            __('Khách hàng', 'tmt-crm'),
            'manage_options',
            'tmt-crm-customer-edit',
            [$this, 'renderPage']
        );
    }

    /**
     * Render form chỉnh sửa
     */
    public function renderPage(): void
    {
        $customerId = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $customer   = $customerId ? $this->companyService->getById($customerId) : null;

        ?>
        <div class="wrap">
            <h1><?php echo $customer ? __('Chỉnh sửa khách hàng', 'tmt-crm') : __('Thêm khách hàng mới', 'tmt-crm'); ?></h1>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('tmt_crm_save_customer'); ?>
                <input type="hidden" name="action" value="tmt_crm_save_customer">
                <input type="hidden" name="id" value="<?php echo esc_attr($customerId); ?>">

                <table class="form-table" role="presentation">
                    <tbody>
                    <tr>
                        <th><label for="company_name"><?php _e('Tên công ty', 'tmt-crm'); ?></label></th>
                        <td>
                            <input type="text" id="company_name" name="company_name"
                                   value="<?php echo esc_attr($customer->getName() ?? ''); ?>"
                                   class="regular-text" required>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="email"><?php _e('Email', 'tmt-crm'); ?></label></th>
                        <td>
                            <input type="email" id="email" name="email"
                                   value="<?php echo esc_attr($customer->getEmail() ?? ''); ?>"
                                   class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th><label for="phone"><?php _e('Số điện thoại', 'tmt-crm'); ?></label></th>
                        <td>
                            <input type="text" id="phone" name="phone"
                                   value="<?php echo esc_attr($customer->getPhone() ?? ''); ?>"
                                   class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th><label for="address"><?php _e('Địa chỉ', 'tmt-crm'); ?></label></th>
                        <td>
                            <textarea id="address" name="address" class="regular-text"
                                      rows="3"><?php echo esc_textarea($customer->getAddress() ?? ''); ?></textarea>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <?php submit_button($customer ? __('Cập nhật khách hàng', 'tmt-crm') : __('Thêm khách hàng', 'tmt-crm')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Xử lý lưu thông tin khách hàng
     */
    public function handleSave(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bạn không có quyền thực hiện hành động này.', 'tmt-crm'));
        }

        check_admin_referer('tmt_crm_save_customer');

        $id      = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name    = sanitize_text_field($_POST['company_name'] ?? '');
        $email   = sanitize_email($_POST['email'] ?? '');
        $phone   = sanitize_text_field($_POST['phone'] ?? '');
        $address = sanitize_textarea_field($_POST['address'] ?? '');

        $dto = new CompanyDTO($id, $name, $email, $phone, $address);

        if ($id) {
            $this->companyService->update($dto);
            $redirectUrl = add_query_arg(['page' => 'tmt-crm-customer-edit', 'id' => $id, 'updated' => 'true'], admin_url('admin.php'));
        } else {
            $newId       = $this->companyService->create($dto);
            $redirectUrl = add_query_arg(['page' => 'tmt-crm-customer-edit', 'id' => $newId, 'created' => 'true'], admin_url('admin.php'));
        }

        wp_redirect($redirectUrl);
        exit;
    }
}
