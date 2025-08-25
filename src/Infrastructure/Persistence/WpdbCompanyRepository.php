<?php

declare(strict_types=1);

namespace TMT\CRM\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Application\DTO\CompanyDTO;
use TMT\CRM\Domain\Repositories\CompanyRepositoryInterface;

/**
 * Repository: wpdb-based cho bảng companies.
 */
final class WpdbCompanyRepository implements CompanyRepositoryInterface
{
    private wpdb $db;
    private string $table;

    public function __construct(wpdb $db, string $table_name = '')
    {
        $this->db    = $db;
        $this->table = $table_name ?: ($db->prefix . 'tmt_crm_companies');
    }

    public function find_by_id(int $id): ?CompanyDTO
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = %d";
        $row = $this->db->get_row($this->db->prepare($sql, $id), ARRAY_A);
        return $row ? $this->map_row_to_dto($row) : null;
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
        return $row ? $this->map_row_to_dto($row) : null;
    }

    public function list_paginated(int $page, int $per_page, array $filters = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['keyword'])) {
            $kw = '%' . $this->db->esc_like(trim((string)$filters['keyword'])) . '%';
            $where[] = "(name LIKE %s OR tax_code LIKE %s OR email LIKE %s OR phone LIKE %s)";
            array_push($params, $kw, $kw, $kw, $kw);
        }

        $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $orderby = $filters['orderby'] ?? 'id';
        $order   = strtoupper((string)($filters['order'] ?? 'DESC'));
        $allowed = ['id', 'name', 'tax_code', 'email', 'phone', 'created_at', 'updated_at'];
        if (!in_array($orderby, $allowed, true)) {
            $orderby = 'id';
        }
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }

        $offset = ($page - 1) * $per_page;

        $sql = "SELECT * FROM {$this->table} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $rows = $this->db->get_results($this->db->prepare($sql, ...$params), ARRAY_A);
        return array_map([$this, 'map_row_to_dto'], $rows ?: []);
    }

    public function count_all(array $filters = []): int
    {
        $where  = [];
        $params = [];

        if (!empty($filters['keyword'])) {
            $kw = '%' . $this->db->esc_like(trim((string)$filters['keyword'])) . '%';
            $where[] = "(name LIKE %s OR tax_code LIKE %s OR email LIKE %s OR phone LIKE %s)";
            array_push($params, $kw, $kw, $kw, $kw);
        }

        $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT COUNT(*) FROM {$this->table} {$where_sql}";

        if (!empty($params)) {
            return (int)$this->db->get_var($this->db->prepare($sql, ...$params));
        }
        return (int)$this->db->get_var($sql);
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
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $format = ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'];

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
            'updated_at' => current_time('mysql'),
        ];
        $format = ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'];

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

    /******Helper**** */
    private function map_row_to_dto(array $row): CompanyDTO
    {
        return new CompanyDTO(
            (int)$row['id'],
            (string)$row['name'],
            (string)$row['tax_code'],
            (string)$row['address'],
            $row['phone']   ?? null,
            $row['email']   ?? null,
            $row['website'] ?? null,
            $row['note']    ?? null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
