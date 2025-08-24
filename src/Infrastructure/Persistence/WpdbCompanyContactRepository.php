<?php

declare(strict_types=1);

namespace TMT\CRM\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Application\DTO\CompanyContactDTO;
use TMT\CRM\Domain\Repositories\CompanyContactRepositoryInterface;

final class WpdbCompanyContactRepository implements CompanyContactRepositoryInterface
{
    private wpdb $db;
    private string $table;

    public function __construct(wpdb $db, string $table_name = '')
    {
        $this->db    = $db;
        $this->table = $table_name ?: ($db->prefix . 'tmt_crm_company_contacts');
    }

    public function find_by_id(int $id): ?CompanyContactDTO
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = %d";
        $row = $this->db->get_row($this->db->prepare($sql, $id), ARRAY_A);
        return $row ? $this->map_row_to_dto($row) : null;
    }

    public function find_active_contacts_by_company(int $company_id, ?string $role = null): array
    {
        $where  = ["company_id = %d", "(end_date IS NULL OR end_date >= CURDATE())"];
        $params = [$company_id];

        if ($role !== null && $role !== '') {
            $where[]  = "role = %s";
            $params[] = $role;
        }

        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where) . " ORDER BY is_primary DESC, id DESC";
        $rows = $this->db->get_results($this->db->prepare($sql, ...$params), ARRAY_A);

        return array_map([$this, 'map_row_to_dto'], $rows ?: []);
    }

    public function get_primary_contact(int $company_id, string $role): ?CompanyContactDTO
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE company_id = %d AND role = %s
                  AND (end_date IS NULL OR end_date >= CURDATE())
                ORDER BY is_primary DESC, id DESC
                LIMIT 1";
        $row = $this->db->get_row($this->db->prepare($sql, $company_id, $role), ARRAY_A);
        return $row ? $this->map_row_to_dto($row) : null;
    }

    public function upsert(CompanyContactDTO $dto): int
    {
        $data = [
            'company_id'  => $dto->company_id,
            'customer_id' => $dto->customer_id,
            'role'        => $dto->role,
            'title'       => $dto->title,
            'is_primary'  => $dto->is_primary ? 1 : 0,
            'start_date'  => $dto->start_date,
            'end_date'    => $dto->end_date,
            'note'        => $dto->note,
            'created_at'  => $dto->created_at,
            'updated_at'  => $dto->updated_at,
        ];
        $format = ['%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s'];

        if ($dto->id) {
            $this->db->update($this->table, $data, ['id' => $dto->id], $format, ['%d']);
            return (int)$dto->id;
        } else {
            $this->db->insert($this->table, $data, $format);
            return (int)$this->db->insert_id;
        }
    }

    public function end_contact(int $id, string $end_date): bool
    {
        $updated = $this->db->update(
            $this->table,
            ['end_date' => $end_date, 'updated_at' => current_time('mysql')],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );
        return $updated !== false;
    }

    public function delete(int $id): bool
    {
        $deleted = $this->db->delete($this->table, ['id' => $id], ['%d']);
        return $deleted !== false;
    }

    /** Đặt is_primary=0 cho tất cả contact cùng company+role */
    public function clear_primary_for_role(int $company_id, string $role): void
    {
        $sql = "UPDATE {$this->table} SET is_primary = 0 WHERE company_id = %d AND role = %s";
        $this->db->query($this->db->prepare($sql, $company_id, $role));
    }

    /** Helper map */
    private function map_row_to_dto(array $row): CompanyContactDTO
    {
        return new CompanyContactDTO(
            (int)$row['id'],
            (int)$row['company_id'],
            (int)$row['customer_id'],
            (string)$row['role'],
            $row['title'] ?? null,
            (bool)$row['is_primary'],
            $row['start_date'] ?? null,
            $row['end_date'] ?? null,
            $row['note'] ?? null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
