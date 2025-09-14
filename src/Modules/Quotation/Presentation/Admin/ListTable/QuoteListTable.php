<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Quotation\Presentation\Admin\ListTable;

use TMT\CRM\Modules\Quotation\Domain\Repositories\QuoteQueryRepositoryInterface;
use TMT\CRM\Modules\Quotation\Presentation\Admin\Screen\QuoteScreen;


if (!class_exists('\WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class QuoteListTable extends \WP_List_Table
{
    public function __construct(
        private QuoteQueryRepositoryInterface $query_repo
    ) {
        parent::__construct([
            'singular' => 'quote',
            'plural'   => 'quotes',
            'ajax'     => false,
        ]);
    }

    public function get_columns(): array
    {
        return [
            'code'        => __('Mã', 'tmt-crm'),
            'customer'    => __('Khách hàng', 'tmt-crm'),
            'created_at'  => __('Ngày', 'tmt-crm'),
            'expires_at'  => __('Hết hạn', 'tmt-crm'),
            'status'      => __('Trạng thái', 'tmt-crm'),
            'grand_total' => __('Tổng tiền', 'tmt-crm'),
        ];
    }

    public function get_sortable_columns(): array
    {
        return [
            'code'        => ['code', false],
            'created_at'  => ['created_at', true],
            'expires_at'  => ['expires_at', false],
            'status'      => ['status', false],
            'grand_total' => ['grand_total', false],
        ];
    }

    protected function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'customer':
                return esc_html($item['customer_display'] ?? ('KH #' . (int)$item['customer_id']));
            case 'created_at':
            case 'expires_at':
                return $item[$column_name] ? esc_html(date_i18n('Y-m-d', strtotime($item[$column_name]))) : '—';
            case 'status':
                $st = esc_html($item['status']);
                return '<span class="pill ' . $st . '">' . $st . '</span>';
            case 'grand_total':
                $cur = $item['currency'] ?? 'VND';
                $n   = number_format((float)$item['grand_total'], 0, ',', '.');
                return $cur === 'USD' ? '$' . $n : $n . ' ₫';
            default:
                return isset($item[$column_name]) ? esc_html((string)$item[$column_name]) : '';
        }
    }

    protected function column_code($item)
    {
        $code = '<code class="kbd">' . esc_html($item['code']) . '</code>';
        $edit = add_query_arg([
            'page'   => QuoteScreen::PAGE_SLUG,
            'action' => 'edit',
            'id'     => (int)$item['id'],
        ], admin_url('admin.php'));

        $actions = [
            'edit'   => '<a href="' . esc_url($edit) . '">' . __('Xem/Sửa', 'tmt-crm') . '</a>',
            'accept' => '<a href="#" class="tmt-action-accept" data-id="' . (int)$item['id'] . '">' . __('Chấp nhận', 'tmt-crm') . '</a>',
            'order'  => '<a href="#" class="tmt-action-convert" data-id="' . (int)$item['id'] . '">' . __('Chuyển đơn hàng', 'tmt-crm') . '</a>',
        ];

        return $code . $this->row_actions($actions);
    }

    public function no_items()
    {
        _e('Không có báo giá phù hợp.', 'tmt-crm');
    }

    public function prepare_items()
    {
        $per_page = $this->get_items_per_page('tmt_crm_quotes_per_page', 20);
        $paged    = $this->get_paged();
        $orderby  = $_GET['orderby'] ?? 'created_at';
        $order    = $_GET['order']   ?? 'desc';
        $search   = $_REQUEST['s']   ?? '';
        $status   = $_GET['status']  ?? '';

        $res = $this->query_repo->paginate([
            'paged'     => (int)$paged,
            'per_page'  => (int)$per_page,
            'orderby'   => (string)$orderby,
            'order'     => (string)$order,
            'search'    => (string)$search,
            'status'    => (string)$status,
        ]);

        $this->items = $res['items'];
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns(), 'code'];

        $this->set_pagination_args([
            'total_items' => (int)$res['total'],
            'per_page'    => (int)$per_page,
            'total_pages' => max(1, (int)ceil($res['total'] / $per_page)),
        ]);

        // Lưu counts để render views()
        $this->status_counts = $res['status_counts'] ?? [];
    }

    private array $status_counts = [];

    protected function get_views(): array
    {
        $current = $_GET['status'] ?? '';
        $base = remove_query_arg(['status', 'paged']);
        $views = [];

        $all_count = array_sum($this->status_counts);
        $views['all'] = sprintf(
            '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
            esc_url($base),
            $current === '' ? 'current' : '',
            __('Tất cả', 'tmt-crm'),
            (int)$all_count
        );

        foreach (['draft', 'sent', 'accepted'] as $st) {
            $count = (int)($this->status_counts[$st] ?? 0);
            $url = add_query_arg('status', $st, $base);
            $views[$st] = sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                esc_url($url),
                $current === $st ? 'current' : '',
                esc_html($st),
                $count
            );
        }

        return $views;
    }

    // helper
    private function get_paged(): int
    {
        $p = isset($_GET['paged']) ? (int)$_GET['paged'] : 0;
        if ($p < 1) {
            $p = isset($_GET['paged']) ? 1 : (int)($_GET['paged'] ?? 1);
        }
        return max(1, $p);
    }
}
