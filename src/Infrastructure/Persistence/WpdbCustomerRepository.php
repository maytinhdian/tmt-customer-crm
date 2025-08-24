<?php

namespace TMT\CRM\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Application\DTO\CustomerDTO;
use TMT\CRM\Domain\Repositories\CustomerRepositoryInterface;

/**
 * Repository: wpdb-based cho bảng customers.
 * - Map hàng DB <-> CustomerDTO
 * - Phân trang + filter + sort an toàn (prepare)
 * - Ghi nhận NULL đúng chuẩn (format động)
 */
final class WpdbCustomerRepository implements CustomerRepositoryInterface
{
    private wpdb $db;
    private string $table;

    /** Chỉ cho phép sort theo các cột này */
    private static array $sortable_fields = ['id', 'name', 'email', 'phone', 'type', 'created_at'];

    public function __construct(wpdb $db, string $table_name = '')
    {
        $this->db    = $db;
        $this->table = $table_name ?: ($db->prefix . 'tmt_crm_customers');
    }

    public function find_by_id(int $id): ?CustomerDTO
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = %d";
        $row = $this->db->get_row($this->db->prepare($sql, $id), ARRAY_A);

        return $row ? $this->map_row_to_dto($row) : null;
    }

    /** @return CustomerDTO[] */
    public function list_paginated(int $page, int $per_page, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $per_page);

        [$where_sql, $params] = $this->build_where($filters);

        // 👉 xử lý sort động
        $order_by = in_array($filters['orderby'] ?? '', self::$sortable_fields, true) ? $filters['orderby'] : 'id';
        $order    = strtolower($filters['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

        $sql = "SELECT * FROM {$this->table} {$where_sql} ORDER BY `$order_by` $order LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $rows = $this->db->get_results($this->db->prepare($sql, $params), ARRAY_A) ?: [];

        return array_map([$this, 'map_row_to_dto'], $rows);
    }

    public function count_all(array $filters = []): int
    {
        [$where_sql, $params] = $this->build_where($filters);
        $sql = "SELECT COUNT(*) FROM {$this->table} {$where_sql}";

        if (!empty($params)) {
            return (int)$this->db->get_var($this->db->prepare($sql, $params));
        }
        return (int)$this->db->get_var($sql);
    }

    //Tìm số điện thoại hay email trùng
    public function find_by_email_or_phone(?string $email = null, ?string $phone = null, ?int $exclude_id = null): ?CustomerDTO
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tmt_crm_customers';

        $where = [];
        $params = [];

        if ($email) {
            $where[] = 'email = %s';
            $params[] = $email;
        }

        if ($phone) {
            $where[] = 'phone = %s';
            $params[] = $phone;
        }

        if (empty($where)) return null;

        $sql = "SELECT * FROM $table WHERE (" . implode(' OR ', $where) . ")";

        if ($exclude_id) {
            $sql .= " AND id != %d";
            $params[] = $exclude_id;
        }

        $sql .= " LIMIT 1";

        $row = $wpdb->get_row($wpdb->prepare($sql, ...$params), ARRAY_A);

        return $row ? CustomerDTO::from_array($row) : null;
    }


    public function create(CustomerDTO $dto): int
    {
        $now = current_time('mysql');

        $data = [
            'name'       => $dto->name,
            'email'      => $dto->email,
            'phone'      => $dto->phone,
            'address'    => $dto->address,
            'note'       => $dto->note,
            'owner_id'   => $dto->owner_id,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        [$filtered, $format] = $this->normalize_for_db($data);

        $this->db->insert($this->table, $filtered, $format);
        return (int)$this->db->insert_id;
    }

    public function update(CustomerDTO $dto): bool
    {
        if (!$dto->id) return false;

        $data = [
            'name'       => $dto->name,
            'email'      => $dto->email,
            'phone'      => $dto->phone,
            'address'    => $dto->address,
            'note'       => $dto->note,
            'owner_id'   => $dto->owner_id,
            'updated_at' => current_time('mysql'),
        ];

        [$filtered, $format] = $this->normalize_for_db($data);

        return (bool)$this->db->update(
            $this->table,
            $filtered,
            ['id' => (int)$dto->id],
            $format,
            ['%d']
        );
    }

    public function delete(int $id): bool
    {
        return (bool)$this->db->delete($this->table, ['id' => $id], ['%d']);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────

    private function map_row_to_dto(array $row): CustomerDTO
    {
        return new CustomerDTO(
            (int)($row['id'] ?? 0),
            (string)($row['name'] ?? ''),
            $row['email']      ?? null,
            $row['phone']      ?? null,
            $row['address']    ?? null,
            $row['note']       ?? null,
            isset($row['owner_id']) ? (int)$row['owner_id'] : null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }

    /**
     * Xây WHERE an toàn từ filters.
     * @return array{0:string,1:array} [where_sql, params]
     */
    private function build_where(array $filters): array
    {
        $clauses = [];
        $params  = [];

        if (!empty($filters['keyword'])) {
            $kw = '%' . $this->db->esc_like($filters['keyword']) . '%';
            $clauses[] = "(name LIKE %s OR email LIKE %s OR phone LIKE %s OR address LIKE %s)";
            array_push($params, $kw, $kw, $kw, $kw);
        }

        if (!empty($filters['owner_id'])) {
            $clauses[] = "owner_id = %d";
            $params[]  = (int)$filters['owner_id'];
        }

        $where_sql = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
        return [$where_sql, $params];
    }

    /**
     * Biến mảng data thành [data, format] cho wpdb:
     * - BỎ QUA key có giá trị NULL để DB set NULL đúng nghĩa.
     * - Tự suy luận format (%s/%d) theo kiểu PHP của value còn lại.
     */
    private function normalize_for_db(array $data): array
    {
        $filtered = [];
        $format   = [];

        foreach ($data as $key => $value) {
            if ($value === null) continue;

            $filtered[$key] = $value;
            $format[] = is_int($value) ? '%d' : '%s';
        }

        return [$filtered, $format];
    }
}
