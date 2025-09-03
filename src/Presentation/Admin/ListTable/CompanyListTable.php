<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\ListTable;

use TMT\CRM\Shared\Container;
use TMT\CRM\Infrastructure\Security\Capability;
use TMT\CRM\Presentation\Admin\Screen\CompanyContactsScreen;

defined('ABSPATH') || exit;

if (!class_exists('\WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Bảng danh sách Companies (WP_List_Table)
 */
final class CompanyListTable extends \WP_List_Table
{
    private array $items_data = [];
    private int $total_items = 0;
    private int $per_page = 20;

    public function __construct()
    {
        parent::__construct([
            'singular' => 'company',
            'plural'   => 'companies',
            'ajax'     => false,
        ]);
    }

    /**
     * Cột hiển thị
     */
    public function get_columns(): array
    {
        return [
            'cb'         => '<input type="checkbox" />',
            'id'         => 'ID',
            'name'       => 'Tên công ty',
            'tax_code'   => 'Mã số thuế',
            'email'      => 'Email',
            'phone'      => 'Điện thoại',
            'address'    => 'Địa chỉ',
            'owner' => 'Người phụ trách',
            'representer' => 'Người đại diện',
            'created_at' => 'Tạo lúc',
            'updated_at' => 'Cập nhật',
            'contacts'   => __('Liên hệ', 'tmt-crm'),
        ];
    }

    /**
     * Cột sort được
     */
    protected function get_sortable_columns(): array
    {
        return [
            'id'         => ['id', false],
            'name'       => ['name', false],
            'tax_code'   => ['tax_code', false],
            'email'      => ['email', false],
            'phone'      => ['phone', false],
            'created_at' => ['created_at', false],
            'updated_at' => ['updated_at', false],
        ];
    }

    /**
     * Bulk actions
     */
    protected function get_bulk_actions(): array
    {
        return [
            'bulk-delete' => 'Xoá đã chọn',
        ];
    }

    /**
     * Checkbox
     */
    protected function column_cb($item): string
    {
        return sprintf('<input type="checkbox" name="ids[]" value="%d" />', (int)$item['id']);
    }

    /**
     * Cột mặc định
     */
    protected function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
            case 'tax_code':
            case 'email':
            case 'phone':
            case 'owner':
                return esc_html((string)($item[$column_name]) ?? "--");
            case 'representer':
            case 'created_at':
            case 'updated_at':
                return esc_html((string)($item[$column_name] ?? ''));
            case 'address':
                $addr = (string)($item['address'] ?? '');
                $addr = wp_strip_all_tags($addr);
                return esc_html(mb_strimwidth($addr, 0, 80, '…'));
            default:
                return '';
        }
    }

    /**
     * Cột name có row actions
     */
    protected function column_name($item): string
    {
        $id   = (int)$item['id'];
        $name = esc_html((string)$item['name']);

        $edit_url = add_query_arg([
            'page'   => 'tmt-crm-companies',
            'action' => 'edit',
            'id'     => $id,
        ], admin_url('admin.php'));

        $delete_url = wp_nonce_url(
            add_query_arg([
                'action' => 'tmt_crm_company_delete',
                'id'     => $id,
            ], admin_url('admin-post.php')),
            'tmt_crm_company_delete_' . $id
        );

        $actions = [
            'edit'   => sprintf('<a href="%s">Sửa</a>', esc_url($edit_url)),
            'delete' => sprintf('<a href="%s" onclick="return confirm(\'Xoá công ty này?\')">Xoá</a>', esc_url($delete_url)),
        ];

        return sprintf('<strong><a href="%s">%s</a></strong> %s', esc_url($edit_url), $name, $this->row_actions($actions));
    }

    // Hiển thị tên người phụ trách từ owner_id
    public function column_owner($item)
    {
        $oid = isset($item['owner_id']) ? (int) $item['owner_id'] : 0;
        if ($oid <= 0) return '—';

        $u = get_user_by('id', $oid);
        $name = ($u instanceof \WP_User) ? ($u->display_name ?: $u->user_login) : null;

        // // (tuỳ chọn) link tới trang sửa user
        // if ($name && ($link = get_edit_user_link($oid))) {
        //     return sprintf('<a href="%s">%s</a>', esc_url($link), esc_html($name));
        // }
        return $name ? esc_html($name) : sprintf('#%d', $oid);
    }

    /**
     * Hiển thị cột Liên hệ
     * - Liệt kê nhanh 2-3 liên hệ đang active
     * - Nút Thêm liên hệ mở form tạo khách hàng có gán trước company_id
     */

    // protected function column_contacts(array $item): string
    // {
    //     $company_id = isset($item['id']) ? (int)$item['id'] : 0;
    //     if ($company_id <= 0) {
    //         return '—';
    //     }

    //     // 1) Quyền xem: nếu không có -> không hiển thị dữ liệu
    //     if (!current_user_can(Capability::COMPANY_READ)) {
    //         return '—';
    //     }

    //     /** @var CompanyContactRepositoryInterface $contact_repo */
    //     $contact_repo = Container::get('company-contact-repo');

    //     $contacts = $contact_repo->find_active_contacts_by_company($company_id) ?: [];
    //     $preview  = array_slice($contacts, 0, 3);

    //     $names = array_map(static function ($c): string {
    //         $name = esc_html($c['full_name'] ?? ($c['name'] ?? ''));
    //         $role = esc_html($c['role'] ?? '');
    //         return $role ? "{$name} <span style='opacity:.7'>( {$role} )</span>" : $name;
    //     }, $preview);

    //     $extra = count($contacts) > 3
    //         ? sprintf(' <span style="opacity:.7">+%d</span>', count($contacts) - 3)
    //         : '';

    //     $list_html = !empty($names)
    //         ? '<div>' . implode('<br>', $names) . $extra . '</div>'
    //         : '<div style="opacity:.7">—</div>';

    //     // 2) Hành động: tùy quyền mà hiện “Thêm liên hệ | Quản lý/Xem”
    //     $manage_url = $manage_url = add_query_arg(
    //         ['page' => \TMT\CRM\Presentation\Admin\Screen\CompanyContactsScreen::PAGE_SLUG, 'company_id' => $company_id],
    //         admin_url('admin.php')
    //     );

    //     $add_url    = admin_url('admin.php?page=tmt-crm-customers&action=new&company_id=' . $company_id);

    //     $actions = [];

    //     if (current_user_can(Capability::COMPANY_READ)) {
    //         // Có quyền quản lý ⇒ hiện đủ 2 nút
    //         $actions['add_contact'] = sprintf('<a href="%s">%s</a>', esc_url($add_url), esc_html__('Thêm liên hệ', 'tmt-crm'));
    //         $actions['manage']      = sprintf('<a href="%s">%s</a>', esc_url($manage_url), esc_html__('Quản lý', 'tmt-crm'));
    //     } else {
    //         // Chỉ có quyền xem ⇒ chỉ hiện “Xem”
    //         $actions['manage'] = sprintf('<a href="%s">%s</a>', esc_url($manage_url), esc_html__('Xem', 'tmt-crm'));
    //     }

    //     // Style row-actions cho hiện luôn trong cột phụ
    //     $force_show_css = '<style>.tmt-cell-actions .row-actions{display:block;margin-top:4px;}</style>';

    //     return $force_show_css . '<div class="tmt-cell-actions">' . $list_html . $this->row_actions($actions) . '</div>';
    // }

    protected function column_contacts(array $item): string
    {
        $company_id = isset($item['id']) ? (int) $item['id'] : 0;
        if ($company_id <= 0) {
            return '—';
        }

        // 1) Quyền xem tối thiểu
        if (!current_user_can(Capability::COMPANY_READ)) {
            return '—';
        }

        /** @var CompanyContactRepositoryInterface $contact_repo */
        $contact_repo = Container::get('company-contact-repo');

        // Có thể cân nhắc eager-load ở prepare_items() để tránh N+1
        $contacts = $contact_repo->find_active_contacts_by_company($company_id) ?: [];

        // Chuẩn hóa 1 contact về view-model array
        $normalize = static function ($c): array {
            if ($c instanceof CompanyContactDTO) {
                $name = (string) ($c->full_name ?? $c->contact_name ?? ($c->contact_id ? ('#' . (int)$c->contact_id) : ''));
                return [
                    'name'       => $name,
                    'role'       => (string) ($c->role ?? ''),
                    'position'   => (string) ($c->position ?? ''),
                    'is_primary' => (bool) $c->is_primary,
                ];
            }
            if (is_array($c)) {
                $id   = isset($c['contact_id']) ? (int)$c['contact_id'] : 0;
                $name = (string) ($c['full_name'] ?? $c['name'] ?? ($id ? ('#' . $id) : ''));
                return [
                    'name'       => $name,
                    'role'       => (string) ($c['role'] ?? ''),
                    'position'   => (string) ($c['position'] ?? ''),
                    'is_primary' => !empty($c['is_primary']),
                ];
            }
            return ['name' => '', 'role' => '', 'position' => '', 'is_primary' => false];
        };

        $preview   = array_slice($contacts, 0, 3);
        $labels    = [];

        foreach ($preview as $c) {
            $a = $normalize($c);
            if ($a['name'] === '') {
                continue;
            }
            $meta = [];
            if ($a['role'] !== '') {
                $meta[] = esc_html($a['role']);
            }
            if ($a['position'] !== '') {
                $meta[] = esc_html($a['position']);
            }
            if ($a['is_primary']) {
                $meta[] = esc_html__('chính', 'tmt-crm');
            }

            $meta_str = $meta ? ' <small>(' . implode(' · ', $meta) . ')</small>' : '';
            $labels[] = sprintf(
                '<span class="tmt-contact-badge">%s%s</span>',
                esc_html($a['name']),
                $meta_str
            );
        }

        $extra = count($contacts) > 3
            ? sprintf(' <span style="opacity:.7">+%d</span>', count($contacts) - 3)
            : '';

        $list_html = $labels
            ? '<div>' . implode('<br>', $labels) . $extra . '</div>'
            : '<div style="opacity:.7">—</div>';

        // 2) Hành động theo quyền
        $manage_url = add_query_arg(
            [
                'page'       => CompanyContactsScreen::PAGE_SLUG,
                'company_id' => $company_id,
            ],
            admin_url('admin.php')
        );

        // Nếu có flow “tạo khách hàng mới rồi gắn vào công ty”
        $add_url = add_query_arg(
            [
                'page'       => 'tmt-crm-customers',
                'action'     => 'new',
                'company_id' => $company_id,
            ],
            admin_url('admin.php')
        );

        $actions = [];
        if (current_user_can(Capability::COMPANY_READ)) {
            // Tuỳ dự án: quyền thêm có thể nên check Capability::CUSTOMER_CREATE hoặc COMPANY_CONTACT_CREATE
            $actions['add_contact'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url($add_url),
                esc_html__('Thêm liên hệ', 'tmt-crm')
            );
            $actions['manage'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url($manage_url),
                esc_html__('Quản lý', 'tmt-crm')
            );
        } else {
            $actions['manage'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url($manage_url),
                esc_html__('Xem', 'tmt-crm')
            );
        }

        // Ép row-actions hiển thị luôn trong cell
        $force_show_css = '<style>.tmt-cell-actions .row-actions{display:block;margin-top:4px;}</style>';

        return $force_show_css
            . '<div class="tmt-cell-actions">'
            . $list_html
            . $this->row_actions($actions)
            . '</div>';
    }

    /**
     * Gọi trước render: nạp dữ liệu + tính phân trang
     */
    public function prepare_items(): void
    {
        $svc = Container::get('company-service');

        // per_page từ Screen Options
        $this->per_page = (int) get_user_meta(get_current_user_id(), 'tmt_crm_companies_per_page', true);
        if ($this->per_page <= 0) $this->per_page = 20;

        $current_page = $this->get_pagenum();

        $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'id';
        $order   = isset($_GET['order']) ? strtoupper(sanitize_text_field($_GET['order'])) : 'DESC';
        if (!in_array($order, ['ASC', 'DESC'], true)) $order = 'DESC';

        $filters = [
            'keyword' => sanitize_text_field($_GET['s'] ?? ''),
            'orderby' => $orderby,
            'order'   => $order,
        ];

        $result = $svc->get_paged($current_page, $this->per_page, $filters);

        // Convert DTO -> array để hiển thị nhanh
        $this->items_data = array_map(function ($dto) {
            return [
                'id'         => $dto->id,
                'name'       => $dto->name,
                'tax_code'   => $dto->tax_code,
                'email'      => $dto->email,
                'phone'      => $dto->phone,
                'address'    => $dto->address,
                'owner_id' => $dto->owner_id,
                'representer' => $dto->representer,
                'created_at' => $dto->created_at,
                'updated_at' => $dto->updated_at,
            ];
        }, $result['items']);

        $this->total_items = (int)$result['total'];

        $screen   = get_current_screen();
        $hidden   = get_hidden_columns($screen); // ✅ cột bị ẩn theo user prefs

        $this->_column_headers = [
            $this->get_columns(),
            $hidden,
            [],
            $this->get_sortable_columns(),
        ];

        $this->items = $this->items_data;

        $this->set_pagination_args([
            'total_items' => $this->total_items,
            'per_page'    => $this->per_page,
            'total_pages' => (int)ceil($this->total_items / $this->per_page),
        ]);
    }

    /* ===================== Helpers ===================== */

    /** Kiểm tra quyền, nếu không đủ -> die với thông báo */
    private static function ensure_capability(string $capability, string $message): void
    {
        if (!current_user_can($capability)) {
            wp_die($message);
        }
    }
}
