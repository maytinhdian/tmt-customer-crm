<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Company\Presentation\Admin\ListTable;

use TMT\CRM\Core\Settings\Settings;
use TMT\CRM\Modules\Company\Application\DTO\CompanyDTO;
use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Modules\Company\Presentation\Admin\Screen\CompanyScreen;
use TMT\CRM\Shared\Infrastructure\Security\Capability;
use TMT\CRM\Shared\Presentation\Support\AdminPostHelper;
use TMT\CRM\Modules\Contact\Application\DTO\CompanyContactDTO;

defined('ABSPATH') || exit;

if (!class_exists('\WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Bảng danh sách Companies (WP_List_Table)
 */
final class CompanyListTable extends \WP_List_Table
{
    /** @var 'active'|'deleted'|'all' */
    private string $status_view = 'active';
    private array $items_data = [];
    private int $total_items = 0;
    private array $counts = ['all' => 0, 'active' => 0, 'deleted' => 0];
    private int $per_page = 20;

    private function normalize_status_view(?string $v): string
    {
        $v = sanitize_key((string) $v);
        return in_array($v, ['active', 'deleted', 'all'], true) ? $v : 'active';
    }

    public function __construct(?string $status_view = null)
    {
        parent::__construct([
            'singular' => 'company',
            'plural'   => 'companies',
            'ajax'     => false,
        ]);

        $request_status   = isset($_GET['status']) ? (string) $_GET['status'] : null;
        $this->status_view = $this->normalize_status_view($request_status ?? $status_view);
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
        if ($this->status_view === 'deleted') {
            return [
                'restore' => esc_html__('Khôi phục', 'tmt-crm'),
                'purge'   => esc_html__('Xoá vĩnh viễn', 'tmt-crm'),
            ];
        }

        return [
            'bulk-delete' => 'Xoá đã chọn',
            'soft_delete' => esc_html__('Xoá mềm', 'tmt-crm'),
        ];
    }

    /** Tabs Tất cả / Đang hoạt động / Đã xoá */
    protected function get_views(): array
    {
        $base = add_query_arg(['page' => sanitize_key($_GET['page'] ?? '')]);
        $make = function (string $key, string $label) use ($base) {
            $url    = esc_url(add_query_arg(['status' => $key], $base));
            $active = $this->status_view === $key ? ' class="current"' : '';
            $count  = (int) ($this->counts[$key] ?? 0);
            return "<a href='{$url}'{$active}>{$label} <span class='count'>({$count})</span></a>";
        };

        return [
            'all'     => $make('all',     esc_html__('Tất cả', 'tmt-crm')),
            'active'  => $make('active',  esc_html__('Đang hoạt động', 'tmt-crm')),
            'deleted' => $make('deleted', esc_html__('Đã xoá', 'tmt-crm')),
        ];
    }

    /** Dải chú thích/legend + filter nho nhỏ ở thanh trên (giống ảnh) */
    // protected function extra_tablenav($which)
    // {
    //     if ($which !== 'top') {
    //         return;
    //     }

    //     echo '<div class="alignleft actions tmt-actions-legend">';
    //     echo '<label class="tmt-legend"><input type="checkbox" checked> ' . esc_html__('Bản ghi hoạt động', 'tmt-crm') . '</label>';
    //     echo ' &nbsp; ';
    //     echo '<label class="tmt-legend"><input type="checkbox" ' . ($this->status_view === 'deleted' ? 'checked' : '') . '> ' . esc_html__('Bản ghi đã xoá mềm (có thể khôi phục)', 'tmt-crm') . '</label>';
    //     echo '</div>';
    // }

    protected function extra_tablenav($which)
    {
        if ($which !== 'top') return;

        $cur = $this->status_view; // 'active'|'deleted'|'all'

        echo '<div class="alignleft actions tmt-actions-legend" id="tmt-status-switch">';
        echo '<label class="tmt-legend"><input type="radio" name="tmt_status" value="active" '
            . ($cur === 'active' ? 'checked' : '') . '> '
            . esc_html__('Bản ghi hoạt động', 'tmt-crm') . '</label>';
        echo ' &nbsp; ';
        echo '<label class="tmt-legend"><input type="radio" name="tmt_status" value="deleted" '
            . ($cur === 'deleted' ? 'checked' : '') . '> '
            . esc_html__('Bản ghi đã xoá mềm (có thể khôi phục)', 'tmt-crm') . '</label>';
        echo ' &nbsp; ';

        // Link “Tất cả” để quay về all
        $all_url = esc_url(add_query_arg(['status' => 'all']));
        echo '<a href="' . $all_url . '" style="margin-left:8px;">' . esc_html__('Tất cả', 'tmt-crm') . '</a>';
        echo '</div>';

        // Inline JS điều hướng
        echo '<script>
                (function(){
                    var box = document.getElementById("tmt-status-switch");
                    if(!box) return;
                        box.addEventListener("change", function(e){
                            if(e.target && e.target.name==="tmt_status"){
                            var u = new URL(window.location.href);
                            u.searchParams.set("status", e.target.value);
                            window.location.assign(u.toString());
                            }
                        });
                    })();
            </script>';
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
        $title = esc_html((string)$item['name']);

        $edit_url = add_query_arg([
            'page'   => 'tmt-crm-companies',
            'action' => 'edit',
            'id'     => $id,
        ], admin_url('admin.php'));

        if (empty($item['deleted_at'])) {

            // URLs hành động
            $edit_url = add_query_arg([
                'page'   => 'tmt-crm-companies',
                'action' => 'edit',
                'id'     => $id,
            ], admin_url('admin.php'));
            $del_url  = AdminPostHelper::url(
                'tmt_crm_company_soft_delete',
                [
                    'id' =>  $id,
                ],
                'tmt_crm_company_soft_delete_' . $id,
            );

            $manage_url = add_query_arg([
                'page' => CompanyScreen::PAGE_SLUG,
                'view' => 'overview',
                'id'   => $id,
            ], admin_url('admin.php'));

            $actions['edit'] = sprintf('<a href="%s">Sửa</a>', esc_url($edit_url));
            $actions['del']  = sprintf('<a href="%s" onclick="return confirm(\'Xoá công ty này?\')">Xoá</a>', esc_url($del_url));
            $actions['more'] = sprintf('<a href="%s">Quản lý</a>', esc_url($manage_url));

            return "<strong>{$title}</strong>" . $this->row_actions($actions);
        }
        if (!empty($item['deleted_at'])) {
            $restore_url  = AdminPostHelper::url(
                'tmt_crm_company_restore',
                [
                    'id' =>  $id,
                ],
                'tmt_crm_company_restore_' . $id,
            );
            $purge_url  = AdminPostHelper::url(
                'tmt_crm_company_purge',
                [
                    'id' =>  $id,
                ],
                'tmt_crm_company_purge_' . $id,
            );
            // Hàng đã xoá mềm
            $badge = '<span class="tmt-badge tmt-badge--deleted">' . esc_html__('ĐÃ XOÁ', 'tmt-crm') . '</span>';
            $actions['restore'] = sprintf('<a class="tmt-restore" href="%s">%s</a>', esc_url($restore_url), esc_html__('Khôi phục', 'tmt-crm'));
            $actions['purge']   = sprintf('<a class="tmt-purge" href="%s" onclick="return confirm(\'' . esc_js(__('Xoá vĩnh viễn?', 'tmt-crm')) . '\')">' . esc_html__('Xoá vĩnh viễn', 'tmt-crm') . '</a>', esc_url($purge_url));
        }

        $meta = sprintf(
            '<div class="tmt-deleted-meta">%s <b>%s</b> • %s %s • %s %s</div>',
            esc_html__('Bởi:', 'tmt-crm'),
            esc_html($item['deleted_by_name'] ?? '—'),
            esc_html__('Lúc:', 'tmt-crm'),
            esc_html($item['deleted_at']),
            esc_html__('Lý do:', 'tmt-crm'),
            esc_html($item['delete_reason'] ?? '—'),
        );

        return "<strong class='tmt-text-deleted'>{$title}</strong> {$badge}" . $this->row_actions($actions) . $meta;
    }

    // Hiển thị tên người phụ trách từ owner_id
    public function column_owner($item)
    {
        $oid = isset($item['owner_id']) ? (int) $item['owner_id'] : 0;
        if ($oid <= 0) return '—';

        $u = get_user_by('id', $oid);
        $name = ($u instanceof \WP_User) ? ($u->display_name ?: $u->user_login) : null;

        // (tuỳ chọn) link tới trang sửa user
        if ($name && ($link = get_edit_user_link($oid))) {
            return sprintf('<a href="%s">%s</a>', esc_url($link), esc_html($name));
        }

        return $name ? esc_html($name) : sprintf('#%d', $oid);
    }

    /**
     * Hiển thị cột Liên hệ
     * - Liệt kê nhanh 2-3 liên hệ đang active
     * - Nút Thêm liên hệ mở form tạo khách hàng có gán trước company_id
     */

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
                $name = (string) ($c->full_name ?? $c->contact_name ?? ($c->created_by ? ('#' . (int)$c->created_by) : ''));
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
                'page'       => CompanyScreen::PAGE_SLUG,
                'tab'        => 'contacts',
                'company_id' => (int) $company_id,
            ],
            admin_url('admin.php')
        );
        // Nếu có flow “tạo khách hàng mới rồi gắn vào công ty”
        $add_url = add_query_arg(
            [
                'page'       => 'tmt-crm-customers',
                'action'     => 'add',
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

        // 1) Per page: Screen Options → Core Settings → 20
        $default_per_page = (int) Settings::get('per_page', 20);
        $this->per_page = $this->get_items_per_page(CompanyScreen::OPTION_PER_PAGE, $default_per_page);
        $current_page = max(1, (int) $this->get_pagenum());

        // 2) Sort & search (có whitelist)
        $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'id';
        $order   = isset($_GET['order']) ? strtoupper(sanitize_text_field($_GET['order'])) : 'DESC';
        if (!in_array($order, ['ASC', 'DESC'], true)) $order = 'DESC';

        $filters = [
            'keyword' => sanitize_text_field($_GET['s'] ?? ''),
            'orderby' => $orderby,
            'order'   => $order,
            'status_view' => $this->status_view, // 👈 thêm dòng này
        ];

        $svc = Container::get('company-service');
        $result = $svc->get_paged($current_page, $this->per_page, $filters);

        $this->counts = method_exists($svc, 'count_for_tabs')
            ? $svc->count_for_tabs()
            : ['all' => $result['total'], 'active' => $result['total'], 'deleted' => 0];

        // Convert DTO -> array để hiển thị nhanh
        $this->items_data = array_map(function (CompanyDTO $dto) {
            return [
                'id'                    => $dto->id,
                'name'                  => $dto->name,
                'tax_code'              => $dto->tax_code,
                'email'                 => $dto->email,
                'phone'                 => $dto->phone,
                'address'               => $dto->address,
                'owner_id'              => $dto->owner_id,
                'representer'           => $dto->representer,
                'created_at'            => $dto->created_at,
                'updated_at'            => $dto->updated_at,
                'deleted_at'            => $dto->deleted_at,
                'deleted_by'            => $dto->deleted_by,
                'deleted_by_name'       => $dto->deleted_by_name,
                'delete_reason'         => $dto->delete_reason,
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


    /** Thêm class cho hàng đã xoá mềm để style mờ */
    public function single_row($item)
    {
        $classes = !empty($item['deleted_at']) ? ' class="tmt-row-deleted"' : '';
        echo "<tr{$classes}>";
        $this->single_row_columns($item);
        echo '</tr>';
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
