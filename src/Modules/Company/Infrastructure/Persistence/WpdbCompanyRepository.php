<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Company\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Modules\Company\Application\DTO\CompanyDTO;
use TMT\CRM\Modules\Company\Domain\Repositories\CompanyRepositoryInterface;
use TMT\CRM\Core\Records\Infrastructure\Persistence\Wpdb\Traits\WpdbSoftDeleteTrait;

/**
 * Repository: wpdb-based cho bảng companies.
 */
final class WpdbCompanyRepository implements CompanyRepositoryInterface
{
    use WpdbSoftDeleteTrait;

    // private wpdb $db;
    // private string $table;

    public function __construct(wpdb $db, string $table_name = '')
    {
        $this->db    = $db;
        $this->table = $table_name ?: ($db->prefix . 'tmt_crm_companies');
    }

    public function find_by_id(int $id): ?CompanyDTO
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = %d";
        $row = $this->db->get_row($this->db->prepare($sql, $id), ARRAY_A);
        return $row ? CompanyDTO::from_array($row) : null;
    }

    public function find_by_tax_code(string $tax_code, ?int $exclude_id = null): ?CompanyDTO
    {
        $tax_code = trim($tax_code);
        if ($tax_code === '') {
            return null;
        }

        if ($exclude_id) {
            $sql = "SELECT * FROM {$this->table} WHERE tax_code = %s AND id <> %d";
            $row = $this->db->get_row($this->db->prepare($sql, $tax_code, $exclude_id), ARRAY_A);
        } else {
            $sql = "SELECT * FROM {$this->table} WHERE tax_code = %s";
            $row = $this->db->get_row($this->db->prepare($sql, $tax_code), ARRAY_A);
        }
        return $row ? CompanyDTO::from_array($row) : null;
    }


    public function list_paginated(int $page, int $per_page, array $filters = []): array
    {
        // Chuẩn hoá phân trang
        $page     = max(1, $page);
        $per_page = max(1, $per_page);
        $offset   = ($page - 1) * $per_page;

        // Bảng (đã prefix). Nếu có full_table() thì dùng cho gọn.
        $t = method_exists($this, 'full_table')
            ? $this->full_table()
            : ($this->db->prefix . $this->table);

        // --- WHERE ---
        $where  = [];
        $params = [];

        // Lọc theo soft-delete: active|deleted|all (mặc định active)
        $status = $filters['status_view'] ?? 'active';
        if ($status === 'active') {
            $where[] = 'deleted_at IS NULL';
        } elseif ($status === 'deleted') {
            $where[] = 'deleted_at IS NOT NULL';
        }

        // Từ khoá
        if (!empty($filters['keyword'])) {
            $kw = '%' . $this->db->esc_like(trim((string) $filters['keyword'])) . '%';
            $where[] = '(name LIKE %s OR tax_code LIKE %s OR email LIKE %s OR phone LIKE %s)';
            array_push($params, $kw, $kw, $kw, $kw);
        }

        $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // --- ORDER BY ---
        $orderby = (string) ($filters['orderby'] ?? 'id');
        $allowed = ['id', 'name', 'tax_code', 'email', 'phone', 'owner_id', 'representer', 'created_at', 'updated', 'updated_at'];
        if (!in_array($orderby, $allowed, true)) {
            $orderby = 'id';
        }
        if ($orderby === 'updated') $orderby = 'updated_at';

        $order = strtoupper((string) ($filters['order'] ?? 'DESC'));
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }

        // --- COUNT tổng ---
        $sql_count = "SELECT COUNT(*) FROM `{$this->table}` {$where_sql}";
        $total = $params
            ? (int) $this->db->get_var($this->db->prepare($sql_count, ...$params))
            : (int) $this->db->get_var($sql_count);

        // --- ITEMS (KHÔNG join user) ---
        $sql_items = "
        SELECT
            id, name, tax_code, email, phone, address,
            owner_id, representer, created_at, updated_at,
            deleted_at, deleted_by, delete_reason
        FROM `{$this->table}`
        {$where_sql}
        ORDER BY {$orderby} {$order}
        LIMIT %d OFFSET %d
    ";
        $params_items = array_merge($params, [$per_page, $offset]);

        $rows = $this->db->get_results($this->db->prepare($sql_items, ...$params_items), ARRAY_A) ?: [];

        // Map -> DTO (hàm bạn đã có)
        $items = array_map(fn($row) => CompanyDTO::from_array($row), $rows);

        return $items;
    }


    /**
     * Đếm Company theo 2 điều kiện soft-delete:
     * - deleted = 'exclude' (mặc định): chỉ bản ghi chưa xóa mềm
     * - deleted = 'only'            : chỉ bản ghi đã xóa mềm
     * Cách dùng nhanh :
     * - $total_active = $repo->count_all(['deleted' => 'exclude']); // đang hoạt động
     * - $total_trashed = $repo->count_all(['deleted' => 'only']);   // đã xóa mềm
     * - $total_all = $repo->count_all(['deleted' => 'include']);    // mọi bản ghi
     */
    public function count_all(array $filters = []): int
    {
        /** @var \wpdb $db */
        $db = $this->db;

        $where = [];
        $deleted = $filters['deleted'] ?? 'exclude';

        if ($deleted === 'exclude') {
            $where[] = "deleted_at IS NULL";
        } elseif ($deleted === 'only') {
            $where[] = "deleted_at IS NOT NULL";
        }
        // Nếu truyền gì khác, coi như 'include' -> không thêm điều kiện

        $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT COUNT(*) FROM {$this->table} {$where_sql}";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return (int) $db->get_var($sql);
    }


    /**
     * Chuẩn hoá 'Y-m-d' thành 'Y-m-d 00:00:00' (start) hoặc '23:59:59' (end).
     */
    private function normalize_datetime(string $value, string $mode = 'start'): string
    {
        $value = trim($value);
        if (strpos($value, ' ') === false) {
            return $mode === 'end' ? ($value . ' 23:59:59') : ($value . ' 00:00:00');
        }
        return $value;
    }



    public function insert(CompanyDTO $dto): int
    {
        $data = [
            'name'       => $dto->name,
            'tax_code'   => $dto->tax_code,
            'address'    => $dto->address,
            'phone'      => $dto->phone,
            'email'      => $dto->email,
            'website'    => $dto->website,
            'note'       => $dto->note,
            'owner_id'    => $dto->owner_id,     // ⬅️
            'representer' => $dto->representer,  // ⬅️
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $format = ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s'];


        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $ok = $this->db->insert($this->table, $data, $format);
        return $ok ? (int)$this->db->insert_id : 0;
    }

    public function update(CompanyDTO $dto): bool
    {
        if (!$dto->id) return false;

        $data = [
            'name'       => $dto->name,
            'tax_code'   => $dto->tax_code,
            'address'    => $dto->address,
            'phone'      => $dto->phone,
            'email'      => $dto->email,
            'website'    => $dto->website,
            'note'       => $dto->note,
            'owner_id'    => $dto->owner_id,     // ⬅️
            'representer' => $dto->representer,  // ⬅️
            'updated_at' => current_time('mysql'),
        ];
        $format = ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s'];

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $ok = $this->db->update($this->table, $data, ['id' => $dto->id], $format, ['%d']);
        return (bool)$ok;
    }

    public function delete(int $id): bool
    {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $ok = $this->db->delete($this->table, ['id' => $id], ['%d']);
        return (bool)$ok;
    }


    public function search_for_select(string $keyword, int $page, int $per_page = 20): array
    {
        $kw = '%' . $this->db->esc_like($keyword) . '%';
        $offset = max(0, ($page - 1) * $per_page);

        $sql  = "SELECT SQL_CALC_FOUND_ROWS id, name FROM {$this->table}
                 WHERE name LIKE %s ORDER BY name ASC LIMIT %d OFFSET %d";
        $rows = $this->db->get_results($this->db->prepare($sql, $kw, $per_page, $offset), ARRAY_A);
        $total = (int) $this->db->get_var('SELECT FOUND_ROWS()');

        return ['items' => $rows ?: [], 'total' => $total];
    }

    public function find_name_by_id(int $id): ?string
    {
        $sql = "SELECT name FROM {$this->table} WHERE id = %d";
        $val = $this->db->get_var($this->db->prepare($sql, $id));
        return $val !== null ? (string)$val : null;
    }

    public function mark_deleted(int $id, int $actor_id, ?string $reason = null): void
    {

        $this->db->update($this->table, [
            'deleted_at'   => current_time('mysql'),
            'deleted_by'   => $actor_id,
            'delete_reason' => $reason,
        ], ['id' => $id], ['%s', '%d', '%s'], ['%d']);
    }

    public function restore(int $id, int $actor_id): void
    {

        $this->db->update($this->table, [
            'deleted_at'    => null,
            'deleted_by'    => null,
            'delete_reason' => null,
        ], ['id' => $id], ['%s', '%d', '%s'], ['%d']);
    }

    public function purge(int $id, int $actor_id): void
    {

        $this->db->delete($this->table, ['id' => $id], ['%d']);
    }

    public function exists_active(int $id): bool
    {

        $sql = $this->db->prepare("SELECT 1 FROM {$this->table} WHERE id=%d AND deleted_at IS NULL", $id);
        return (bool)  $this->db->get_var($sql);
    }

    /** @return array{active:int,deleted:int} */
    public function count_by_status(): array
    {

        $active  = (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->table} WHERE deleted_at IS NULL");
        $deleted = (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->table} WHERE deleted_at IS NOT NULL");
        return ['active' => $active, 'deleted' => $deleted];
    }
    public function count_for_tabs(): array
    {
        $t = $this->full_table();
        // 1 query: COUNT(*) + SUM(CASE WHEN)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $row = $this->db->get_row("
        SELECT 
            COUNT(*) AS all_count,
            SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END) AS active_count,
            SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) AS deleted_count
        FROM `{$this->table}`
    ", ARRAY_A);

        return [
            'all'     => (int) ($row['all_count'] ?? 0),
            'active'  => (int) ($row['active_count'] ?? 0),
            'deleted' => (int) ($row['deleted_count'] ?? 0),
        ];
    }


    // /******Helper**** */
    // private function map_row_to_dto(array $row): CompanyDTO
    // {
    //     return new CompanyDTO(
    //         (int)$row['id'],
    //         (string)$row['name'],
    //         (string)$row['tax_code'],
    //         (string)$row['address'],
    //         $row['phone']   ?? null,
    //         $row['email']   ?? null,
    //         $row['website'] ?? null,
    //         $row['note']    ?? null,
    //         isset($row['owner_id']) ? (int)$row['owner_id'] : null,     // ⬅️
    //         $row['representer'] ?? null,                                 // ⬅️
    //         $row['created_at'] ?? null,
    //         $row['updated_at'] ?? null,
    //         $row['deleted_at'] ?? null,
    //         (int)$row['deleted_by'] ?? null,
    //         $row['deleted_reason'] ?? null,

    //     );
    // }
}
