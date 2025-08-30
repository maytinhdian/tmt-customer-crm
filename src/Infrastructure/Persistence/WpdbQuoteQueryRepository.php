<?php
declare(strict_types=1);

namespace TMT\CRM\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Domain\Repositories\QuoteQueryRepositoryInterface;

final class WpdbQuoteQueryRepository implements QuoteQueryRepositoryInterface
{
    public function __construct(private wpdb $db) {}
    private function tq(): string { return $this->db->prefix . 'tmt_crm_quotes'; }

    public function paginate(array $args): array
    {
        $paged    = max(1, (int)($args['paged']     ?? 1));
        $per_page = max(1, (int)($args['per_page']  ?? 20));
        $search   = trim((string)($args['search']   ?? ''));
        $status   = trim((string)($args['status']   ?? ''));
        $orderby  = strtolower((string)($args['orderby'] ?? 'created_at'));
        $order    = strtoupper((string)($args['order']   ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        // Bảo vệ cột sort
        $map = [
            'code'        => 'code',
            'created_at'  => 'created_at',
            'expires_at'  => 'expires_at',
            'grand_total' => 'grand_total',
            'status'      => 'status',
        ];
        $order_col = $map[$orderby] ?? 'created_at';

        $where   = ['1=1'];
        $params  = [];

        if ($search !== '') {
            $where[] = "(code LIKE %s OR note LIKE %s)";
            $like = '%' . $this->db->esc_like($search) . '%';
            $params[] = $like; $params[] = $like;
        }
        if ($status !== '') {
            $where[] = "status = %s";
            $params[] = $status;
        }

        $where_sql = implode(' AND ', $where);
        $offset    = ($paged - 1) * $per_page;

        // Count tổng dòng
        $sql_count = "SELECT COUNT(*) FROM {$this->tq()} WHERE {$where_sql}";
        $total = (int)$this->db->get_var($this->db->prepare($sql_count, ...$params));

        // Lấy dữ liệu trang hiện tại
        $sql = "
            SELECT id, code, status, customer_id, company_id, owner_id,
                   currency, subtotal, discount_total, tax_total, grand_total,
                   note, created_at, expires_at
            FROM {$this->tq()}
            WHERE {$where_sql}
            ORDER BY {$order_col} {$order}
            LIMIT %d OFFSET %d
        ";
        $items = $this->db->get_results(
            $this->db->prepare($sql, ...array_merge($params, [$per_page, $offset])),
            ARRAY_A
        ) ?: [];

        // Hiển thị tên KH: cho phép filter tùy hệ thống dùng cột nào
        foreach ($items as &$row) {
            $row['customer_display'] = apply_filters(
                'tmt_crm/quote_list/customer_display',
                'KH #' . (int)$row['customer_id'],
                $row
            );
        }

        // Đếm theo status để làm views
        $sql_sc = "
            SELECT status, COUNT(*) AS c
            FROM {$this->tq()}
            GROUP BY status
        ";
        $raw = $this->db->get_results($sql_sc, ARRAY_A) ?: [];
        $status_counts = [];
        foreach ($raw as $r) {
            $status_counts[(string)$r['status']] = (int)$r['c'];
        }

        return [
            'items' => $items,
            'total' => $total,
            'status_counts' => $status_counts,
        ];
    }
}
