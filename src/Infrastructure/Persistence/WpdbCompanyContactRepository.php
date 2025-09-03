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
    private string $tblCompanyContacts;

    public function __construct(wpdb $db, string $table_name = '')
    {
        $this->db    = $db;
        $this->table = $table_name ?: ($db->prefix . 'tmt_crm_company_contacts');
        $this->tblCompanyContacts = $this->table;
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

    public function attach_customer(CompanyContactDTO $dto): int
    {
        // Lấy mảng an toàn từ DTO (DTO của bạn có trait AsArrayTrait → to_array()).
        $arr = method_exists($dto, 'to_array') ? $dto->to_array() : [];

        // Bắt buộc
        $company_id  = (int)($arr['company_id'] ?? 0);
        $customer_id = (int)($arr['customer_id'] ?? 0);

        // Tuỳ chọn
        $role       = isset($arr['role']) ? (string)$arr['role'] : '';
        $position   = isset($arr['position']) ? (string)$arr['position'] : null;
        $start_date = isset($arr['start_date']) ? trim((string)$arr['start_date']) : '';
        $end_date   = isset($arr['end_date']) ? trim((string)$arr['end_date']) : '';
        $is_primary = !empty($arr['is_primary']) ? 1 : 0;
        $note       = isset($arr['note']) ? (string)$arr['note'] : null;
        $created_by = isset($arr['created_by']) ? (int)$arr['created_by'] : (int)get_current_user_id();

        $data = [
            'company_id' => $company_id,
            'customer_id' => $customer_id,
            'role'       => $role,
            'is_primary' => $is_primary,
            'created_by' => $created_by,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];
        $format = ['%d', '%d', '%s', '%d', '%d', '%s', '%s'];

        if ($position !== null) {
            $data['position'] = $position;
            $format[] = '%s';
        }
        if ($note     !== null) {
            $data['note']     = $note;
            $format[] = '%s';
        }
        if ($start_date !== '') {
            $data['start_date'] = $start_date;
            $format[] = '%s';
        }
        if ($end_date   !== '') {
            $data['end_date']   = $end_date;
            $format[] = '%s';
        }

        $ok = $this->db->insert($this->table, $data, $format);
        if ($ok === false) {
            throw new \RuntimeException('DB error (attach_customer): ' . $this->db->last_error);
        }
        return (int)$this->db->insert_id;
    }

    public function is_customer_active_in_company(int $company_id, int $customer_id): bool
    {
        $sql = "
            SELECT 1
            FROM {$this->table}
            WHERE company_id = %d
              AND customer_id = %d
              AND (end_date IS NULL OR end_date >= CURDATE())
            LIMIT 1
        ";
        $val = $this->db->get_var($this->db->prepare($sql, $company_id, $customer_id));
        return $val === '1' || $val === 1;
    }

    public function unset_primary(int $company_id): void
    {
        $res = $this->db->update(
            $this->table,
            ['is_primary' => 0],
            ['company_id' => $company_id],
            ['%d'],
            ['%d']
        );
        if ($res === false) {
            throw new \RuntimeException('DB error (unset_primary): ' . $this->db->last_error);
        }
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
            (int)$row['created_by'] ?? null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
