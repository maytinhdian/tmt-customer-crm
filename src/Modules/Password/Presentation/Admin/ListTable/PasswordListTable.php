<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Password\Presentation\Admin\ListTable;

use WP_List_Table;
use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Modules\Password\Application\Services\PasswordService;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class PasswordListTable extends WP_List_Table
{
    private PasswordService $service;

    public function __construct()
    {
        parent::__construct([
            'singular' => 'password',
            'plural'   => 'passwords',
            'ajax'     => false,
        ]);
        $this->service = Container::get(PasswordService::class);
    }

    public function get_columns(): array
    {
        return [
            'cb'       => '<input type="checkbox" />',
            'title'    => __('Tiêu đề', 'tmt-crm'),
            'username' => __('Tài khoản', 'tmt-crm'),
            'url'      => __('URL', 'tmt-crm'),
            'owner'    => __('Owner', 'tmt-crm'),
            'subject'  => __('Subject', 'tmt-crm'),
            'customer_id' => __('Customer ID', 'tmt-crm'),
            'company_id' => __('Company ID', 'tmt-crm'),
            'date'     => __('Ngày', 'tmt-crm'),
        ];
    }

    protected function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="ids[]" value="%d" />', $item->id);
    }
    protected function column_subject($item)
    {
        return esc_html($item->subject); // 'company' | 'customer'
    }
    protected function column_company_id($item)
    {
        if ($item->subject !== 'company') return '';
        // TODO: nếu có tên công ty -> hiển thị tên kèm link
        return $item->company_id ? (int)$item->company_id : '';
    }

    protected function column_customer_id($item)
    {
        if ($item->subject !== 'customer') return '';
        // TODO: nếu có tên KH -> hiển thị tên kèm link
        return $item->customer_id ? (int)$item->customer_id : '';
    }
    protected function column_title($item)
    {
        $view = add_query_arg(['page' => $_REQUEST['page'], 'action' => 'edit', 'id' => $item->id], admin_url('admin.php'));
        $reveal = wp_nonce_url(add_query_arg(['action' => 'reveal', 'id' => $item->id]), 'reveal-password-' . $item->id);
        $actions = [
            'edit'   => '<a href="' . esc_url($view) . '">' . __('Sửa', 'tmt-crm') . '</a>',
            'reveal' => '<a href="' . esc_url($reveal) . '">' . __('Hiện mật khẩu', 'tmt-crm') . '</a>',
        ];
        return sprintf('<strong>%s</strong> %s', esc_html($item->title), $this->row_actions($actions));
    }

    protected function extra_tablenav($which)
    {
        if ($which !== 'top') return;

        $subject     = isset($_GET['subject']) ? sanitize_text_field($_GET['subject']) : '';
        $company_id  = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
        $customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

?>
        <div class="alignleft actions">
            <select name="subject">
                <option value=""><?php esc_html_e('— Subject —', 'tmt-crm'); ?></option>
                <option value="company" <?php selected($subject, 'company');  ?>>Company</option>
                <option value="customer" <?php selected($subject, 'customer'); ?>>Customer</option>
            </select>

            <input type="number" name="company_id" placeholder="<?php esc_attr_e('Company ID', 'tmt-crm'); ?>" value="<?php echo $company_id ?: ''; ?>" />
            <input type="number" name="customer_id" placeholder="<?php esc_attr_e('Customer ID', 'tmt-crm'); ?>" value="<?php echo $customer_id ?: ''; ?>" />

            <?php submit_button(__('Lọc', 'tmt-crm'), 'secondary', '', false); ?>
        </div>
<?php
    }

    public function prepare_items(): void
    {
        $per_page = 20;
        $page = max(1, (int)($_GET['paged'] ?? 1));
        $filters = [
            'q' => sanitize_text_field($_GET['s'] ?? ''),
            'subject'     => isset($_GET['subject']) && in_array($_GET['subject'], ['company', 'customer'], true) ? $_GET['subject'] : null,
            'company_id'  => isset($_GET['company_id']) ? (int)$_GET['company_id'] : null,
            'customer_id' => isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null,
        ];
        $data = $this->service->list($filters, $page, $per_page);

        $this->_column_headers = [$this->get_columns(), [], []];
        $this->items = $data['items'];
        $this->set_pagination_args([
            'total_items' => $data['total'],
            'per_page'    => $per_page,
        ]);
    }

    protected function column_default($item, $column_name)
    {
        return match ($column_name) {
            'username' => esc_html($item->username ?? ''),
            'url'      => $item->url ? '<a href="' . esc_url($item->url) . '" target="_blank">' . esc_html($item->url) . '</a>' : '',
            'owner'    => esc_html(get_userdata($item->owner_id)->display_name ?? ''),
            'date'     => esc_html(mysql2date('Y-m-d H:i', $item->updated_at)),
            'subject'  => esc_html($item->subject ?? ''),
            'customer_id' => esc_html($item->customer_id ?? ''),
            'company_id' => esc_html($item->company_id ?? ''),
            default    => '',
        };
    }
}
