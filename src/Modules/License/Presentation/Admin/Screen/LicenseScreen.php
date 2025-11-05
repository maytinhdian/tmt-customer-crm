<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Presentation\Admin\Screen;

use TMT\CRM\Shared\Presentation\Support\AdminNoticeService;
use TMT\CRM\Domain\Repositories\{CredentialRepositoryInterface, CredentialSeatAllocationRepositoryInterface};
use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Shared\Presentation\Support\AdminPostHelper;
use TMT\CRM\Shared\Presentation\Support\View;

final class LicenseScreen
{
    public const PAGE_SLUG = 'tmt-crm-licenses';



    public static function route(): void
    {
        $view = isset($_GET['view']) ? sanitize_key((string)$_GET['view']) : 'list';
        if ($view === 'edit') {
            self::render_form();
        } else {
            self::render_list();
        }
    }

    /** Danh sách credentials */
    public static function render_list(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Not allowed', 'tmt-crm'));
        }

        $repo  = Container::get(CredentialRepositoryInterface::class);
        $aRepo = Container::get(CredentialSeatAllocationRepositoryInterface::class);

        $q      = isset($_GET['s']) ? sanitize_text_field((string)$_GET['s']) : '';
        $type   = isset($_GET['type']) ? sanitize_text_field((string)$_GET['type']) : '';
        $status = isset($_GET['status']) ? sanitize_text_field((string)$_GET['status']) : '';
        $page   = isset($_GET['paged']) ? max(1, (int)$_GET['paged']) : 1;

        $filter = [];
        if ($q !== '') {
            $filter['q'] = $q;
        }
        if ($type !== '') {
            $filter['type'] = $type;
        }
        if ($status !== '') {
            $filter['status'] = $status;
        }

        $result = $repo->search($filter, $page, 20);
        $items  = $result['items'];
        $total  = (int)($result['total'] ?? 0);

        // (tùy chọn) tính seats_used nhanh (P0 cho đẹp bảng)
        foreach ($items as $dto) {
            $allocs = $aRepo->list_by_credential((int)$dto->id);
            $used   = 0;
            foreach ($allocs as $a) {
                $used += (int)$a->seat_used;
            }
            // có thể set vào $dto nếu DTO cho phép
            // $dto->seats_used = $used;
        }

        $add_url = add_query_arg([
            'page' => self::PAGE_SLUG,
            'view' => 'edit',
            'id'   => 0,
            'tab'  => 'general',
        ], admin_url('admin.php'));

        $add_url = AdminPostHelper::url(
            'tmt_crm_license_open_form',
            [
                'id' => 0,
                'view' => 'edit',
                'tab'  => 'general',
            ]
        );

        View::render_admin_module('license', 'list', [
            'q'       => $q,
            'items'   => $items,
            'total'   => $total,
            'add_url' => $add_url,
            // Truyền kèm filter để template tái sử dụng nếu cần
            'filter'  => ['type' => $type, 'status' => $status],
        ]);
    }

    /** Form tạo/sửa + các tab con */
    public static function render_form(): void
    {
        $screen_id = 'toplevel_page_tmt-crm-license'; // đặt đúng ID
        $errors = AdminNoticeService::take_errors($screen_id); // [] nếu không có

        if (!current_user_can('manage_options')) {
            wp_die(__('Not allowed', 'tmt-crm'));
        }

        $id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $tab  = isset($_GET['tab']) ? sanitize_key((string)$_GET['tab']) : 'general';
        $tabs = [
            'general'     => __('General', 'tmt-crm'),
            'allocations' => __('Allocations', 'tmt-crm'),
            'activations' => __('Activations', 'tmt-crm'),
            'deliveries'  => __('Deliveries', 'tmt-crm'),
        ];
        if (!array_key_exists($tab, $tabs)) {
            $tab = 'general';
        }

        $repo     = Container::get(CredentialRepositoryInterface::class);
        $dto      = $id ? $repo->find_by_id($id) : null;
        $action   = admin_url('admin-post.php');
        $list_url = add_query_arg(['page' => self::PAGE_SLUG], admin_url('admin.php'));

        // Giá trị form General
        $data_general = [
            'id'            => $id,
            'number'        => $dto->number ?? '',
            'type'          => $dto->type ?? 'LICENSE_KEY',
            'label'         => $dto->label ?? '',
            'customer_id'   => $dto->customer_id ?? '',
            'company_id'    => $dto->company_id ?? '',
            'status'        => $dto->status ?? 'active',
            'expires_at'    => $dto->expires_at ?? '',
            'seats_total'   => $dto->seats_total ?? '',
            'sharing_mode'  => $dto->sharing_mode ?? 'none',
            'renewal_of_id' => $dto->renewal_of_id ?? '',
            'owner_id'      => $dto->owner_id ?? '',
            'username'      => $dto->username ?? '',
            'extra_json'    => $dto->extra_json ?? '',
        ];

        // Render shell chung (header + tabs)
        View::render_admin_module('license', 'form', [
            'tabs'      => $tabs,
            'active'    => $tab,
            'id'        => $id,
            'action'    => $action,
            'list_url'  => $list_url,
            'general'   => $data_general,
            'errors'    => $errors,
        ]);
    }
}
