<?php
// src/Infrastructure/Persistence/WpdbEmploymentRepository.php
declare(strict_types=1);

namespace TMT\CRM\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Application\DTO\CustomerEmploymentDTO;
use TMT\CRM\Domain\Repositories\EmploymentRepositoryInterface;

final class WpdbEmploymentRepository implements EmploymentRepositoryInterface
{
    private wpdb $db;
    private string $table;

    public function __construct(wpdb $db, string $table_name = '')
    {
        $this->db    = $db;
        $this->table = $table_name ?: ($db->prefix . 'tmt_crm_customer_employments');
    }

    public function create(CustomerEmploymentDTO $dto): int
    {
        $this->db->insert($this->table, [
            'customer_id' => $dto->customer_id,
            'company_id'  => $dto->company_id,
            'start_date'  => $dto->start_date,
            'end_date'    => $dto->end_date,
            'is_primary'  => $dto->is_primary ? 1 : 0,
        ], ['%d','%d','%s','%s','%d']);

        return (int)$this->db->insert_id;
    }

    public function close_employment(int $employment_id, string $end_date): bool
    {
        return (bool)$this->db->update(
            $this->table,
            ['end_date' => $end_date],
            ['id' => $employment_id],
            ['%s'],
            ['%d']
        );
    }

    public function get_active_by_customer(int $customer_id): ?CustomerEmploymentDTO
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE customer_id = %d AND end_date IS NULL
                ORDER BY start_date DESC, id DESC LIMIT 1";
        $row = $this->db->get_row($this->db->prepare($sql, $customer_id), ARRAY_A);
        return $row ? $this->map_row($row) : null;
    }

    public function list_by_customer(int $customer_id): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE customer_id = %d ORDER BY start_date DESC, id DESC";
        $rows = $this->db->get_results($this->db->prepare($sql, $customer_id), ARRAY_A) ?: [];
        return array_map([$this, 'map_row'], $rows);
    }

    public function list_active_by_company(int $company_id): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE company_id = %d AND end_date IS NULL
                ORDER BY start_date DESC, id DESC";
        $rows = $this->db->get_results($this->db->prepare($sql, $company_id), ARRAY_A) ?: [];
        return array_map([$this, 'map_row'], $rows);
    }

    private function map_row(array $r): CustomerEmploymentDTO
    {
        $dto = new CustomerEmploymentDTO();
        $dto->id          = (int)$r['id'];
        $dto->customer_id = (int)$r['customer_id'];
        $dto->company_id  = (int)$r['company_id'];
        $dto->start_date  = (string)$r['start_date'];
        $dto->end_date    = $r['end_date'] !== null ? (string)$r['end_date'] : null;
        $dto->is_primary  = (bool)$r['is_primary'];
        return $dto;
    }
}
