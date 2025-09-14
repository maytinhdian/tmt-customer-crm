<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Customer\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Modules\Customer\Application\DTO\EmploymentHistoryDTO;
use TMT\CRM\Modules\Customer\Domain\Repositories\EmploymentHistoryRepositoryInterface;

final class WpdbEmploymentHistoryRepository implements EmploymentHistoryRepositoryInterface
{
    private wpdb $db;
    private string $table;

    public function __construct(wpdb $db, string $table_name = '')
    {
        $this->db    = $db;
        $this->table = $table_name ?: ($db->prefix . 'tmt_crm_customer_company_history');
    }

    public function find_by_id(int $id): ?EmploymentHistoryDTO
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = %d";
        $row = $this->db->get_row($this->db->prepare($sql, $id), ARRAY_A);
        return $row ? $this->map_row_to_dto($row) : null;
    }

    public function find_by_customer(int $customer_id): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE customer_id = %d ORDER BY COALESCE(end_date, '9999-12-31') DESC, start_date DESC";
        $rows = $this->db->get_results($this->db->prepare($sql, $customer_id), ARRAY_A);
        return array_map([$this, 'map_row_to_dto'], $rows ?: []);
    }

    public function find_current_company_of_customer(int $customer_id): ?EmploymentHistoryDTO
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE customer_id = %d
                  AND (end_date IS NULL OR end_date >= CURDATE())
                ORDER BY COALESCE(end_date, '9999-12-31') DESC, start_date DESC
                LIMIT 1";
        $row = $this->db->get_row($this->db->prepare($sql, $customer_id), ARRAY_A);
        return $row ? $this->map_row_to_dto($row) : null;
    }

    public function upsert(EmploymentHistoryDTO $dto): int
    {
        $data = [
            'customer_id' => $dto->customer_id,
            'company_id'  => $dto->company_id,
            'title'       => $dto->title,
            'start_date'  => $dto->start_date,
            'end_date'    => $dto->end_date,
            'note'        => $dto->note,
            'created_at'  => $dto->created_at,
            'updated_at'  => $dto->updated_at,
        ];
        $format = ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s'];

        if ($dto->id) {
            $this->db->update($this->table, $data, ['id' => $dto->id], $format, ['%d']);
            return (int)$dto->id;
        } else {
            $this->db->insert($this->table, $data, $format);
            return (int)$this->db->insert_id;
        }
    }

    public function end_employment(int $id, string $end_date): bool
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

    public function list_active_by_company(int $company_id): array
    {
        $sql = "SELECT * FROM {$this->table}
            WHERE company_id = %d
              AND (end_date IS NULL OR end_date >= CURDATE())
            ORDER BY COALESCE(end_date, '9999-12-31') DESC, start_date DESC";
        $rows = $this->db->get_results($this->db->prepare($sql, $company_id), ARRAY_A);

        return array_map([$this, 'map_row_to_dto'], $rows ?: []);
    }

    private function map_row_to_dto(array $row): EmploymentHistoryDTO
    {
        return new EmploymentHistoryDTO(
            (int)$row['id'],
            (int)$row['customer_id'],
            (int)$row['company_id'],
            $row['title'] ?? null,
            $row['start_date'] ?? null,
            $row['end_date'] ?? null,
            $row['note'] ?? null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
