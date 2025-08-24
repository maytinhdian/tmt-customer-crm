<?php
// src/Infrastructure/Persistence/WpdbCompanyContactRoleRepository.php
declare(strict_types=1);

namespace TMT\CRM\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Application\DTO\CompanyContactRoleDTO;
use TMT\CRM\Domain\Repositories\CompanyContactRoleRepositoryInterface;

final class WpdbCompanyContactRoleRepository implements CompanyContactRoleRepositoryInterface
{
    private wpdb $db;
    private string $table_roles;
    private string $table_customers;

    public function __construct(wpdb $db, string $roles_table = '', string $customers_table = '')
    {
        $this->db              = $db;
        $this->table_roles     = $roles_table ?: ($db->prefix . 'tmt_crm_company_contact_roles');
        $this->table_customers = $customers_table ?: ($db->prefix . 'tmt_crm_customers');
    }

    public function assign_role(CompanyContactRoleDTO $dto): int
    {
        $this->db->insert($this->table_roles, [
            'company_id'  => $dto->company_id,
            'customer_id' => $dto->customer_id,
            'role'        => $dto->role,
            'start_date'  => $dto->start_date,
            'end_date'    => $dto->end_date,
        ], ['%d', '%d', '%s', '%s', '%s']);
        return (int)$this->db->insert_id;
    }

    public function end_role(int $id, string $end_date): bool
    {
        return (bool)$this->db->update(
            $this->table_roles,
            ['end_date' => $end_date],
            ['id' => $id],
            ['%s'],
            ['%d']
        );
    }

    public function get_active_by_role(int $company_id, string $role): ?array
    {
        $sql = "SELECT r.id, r.role,
                       c.id AS customer_id, c.name, c.phone, c.email
                FROM {$this->table_roles} r
                JOIN {$this->table_customers} c ON c.id = r.customer_id
                WHERE r.company_id = %d AND r.role = %s AND r.end_date IS NULL
                ORDER BY r.start_date DESC, r.id DESC LIMIT 1";
        $row = $this->db->get_row($this->db->prepare($sql, $company_id, $role), ARRAY_A);
        if (!$row) return null;

        return [
            'id'   => (int)$row['id'],
            'role' => (string)$row['role'],
            'customer' => [
                'id'    => (int)$row['customer_id'],
                'name'  => (string)$row['name'],
                'phone' => $row['phone'] !== null ? (string)$row['phone'] : null,
                'email' => $row['email'] !== null ? (string)$row['email'] : null,
            ],
        ];
    }

    public function list_active(int $company_id): array
    {
        $sql = "SELECT r.id, r.role,
                       c.id AS customer_id, c.name, c.phone, c.email
                FROM {$this->table_roles} r
                JOIN {$this->table_customers} c ON c.id = r.customer_id
                WHERE r.company_id = %d AND r.end_date IS NULL
                ORDER BY r.role ASC, c.name ASC";
        $rows = $this->db->get_results($this->db->prepare($sql, $company_id), ARRAY_A) ?: [];

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id'   => (int)$row['id'],
                'role' => (string)$row['role'],
                'customer' => [
                    'id'    => (int)$row['customer_id'],
                    'name'  => (string)$row['name'],
                    'phone' => $row['phone'] !== null ? (string)$row['phone'] : null,
                    'email' => $row['email'] !== null ? (string)$row['email'] : null,
                ],
            ];
        }
        return $out;
    }
}
