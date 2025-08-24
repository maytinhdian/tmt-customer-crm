<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\Company\Form;

use TMT\CRM\Shared\Container;

final class CompanyRolesBox
{
    /** Render trong trang Add/Edit Company */
    public static function render(int $company_id): void
    {
        $employment_repo = Container::get('employment-history-repo');
        $customer_repo   = Container::get('customer-repo');
        $svc             = Container::get('company-service');

        // Lấy các customer đang active tại company
        $actives    = $employment_repo->list_active_by_company($company_id);
        $active_ids = array_map(fn($e) => (int)$e->customer_id, $actives);
        $options    = $active_ids ? $customer_repo->find_many_by_ids($active_ids) : [];

        $labels = [
            'accounting' => 'Nhân viên kế toán',
            'purchasing' => 'Nhân viên thu mua',
            'invoice'    => 'Người xuất hóa đơn',
        ];

        // Liên hệ hiện hành cho từng role
        $current = [];
        foreach (array_keys($labels) as $role) {
            $current[$role] = $svc->get_active_contact_by_role($company_id, $role);
        }

        wp_nonce_field('tmt_crm_company_roles', '_wpnonce_tmt_crm_company_roles');
?>
        <div class="card tmt-crm-card">
            <h2 class="title">Liên hệ theo vai trò</h2>
            <p class="description">Chỉ hiển thị những người đang “active” tại công ty này.</p>

            <table class="form-table" role="presentation">
                <tbody>
                    <?php foreach ($labels as $role => $label): ?>
                        <tr>
                            <th scope="row"><label><?= esc_html($label) ?></label></th>
                            <td>
                                <select
                                    class="tmt-select2"
                                    name="company_roles[<?= esc_attr($role) ?>]"
                                    style="min-width:320px"
                                    data-placeholder="— Chưa gán —">
                                    <option value="">— Chưa gán —</option>
                                    <?php foreach ($options as $opt): ?>
                                        <option value="<?= (int)$opt['id'] ?>"
                                            <?= (int)($current[$role]['customer']['id'] ?? 0) === (int)$opt['id'] ? 'selected' : '' ?>>
                                            <?= esc_html($opt['name'] . (!empty($opt['phone']) ? " — {$opt['phone']}" : '')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p><em>Đổi người → vai trò cũ tự kết thúc (end_date = hôm qua), vai trò mới bắt đầu từ hôm nay.</em></p>
        </div>
<?php
    }

    /** Gọi sau khi lưu master-data của Company */
    public static function handle_post(int $company_id): void
    {
        if (
            !isset($_POST['_wpnonce_tmt_crm_company_roles'])
            || !wp_verify_nonce($_POST['_wpnonce_tmt_crm_company_roles'], 'tmt_crm_company_roles')
        ) {
            return;
        }

        $roles = array_map('sanitize_text_field', (array)($_POST['company_roles'] ?? []));
        $svc   = Container::get('company-service');

        foreach ($roles as $role => $customer_id) {
            if ($customer_id === '') continue;

            try {
                $svc->assign_contact_role(
                    company_id: (int)$company_id,
                    customer_id: (int)$customer_id,
                    role: $role,
                    start_date: date('Y-m-d')
                );
            } catch (\Throwable $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[tmt-crm] assign_contact_role failed: ' . $e->getMessage());
                }
            }
        }
    }
}
