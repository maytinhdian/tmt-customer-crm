<?php
// src/Infrastructure/Persistence/wpdb-company-repository.php
namespace TMT\CRM\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Domain\Entities\Company;
use TMT\CRM\Domain\Repositories\CompanyRepositoryInterface;

final class WpdbCompanyRepository implements CompanyRepositoryInterface
{
    public function __construct(private wpdb $db) {}
    private function table(): string
    {
        return $this->db->prefix . 'crm_companies';
    }

    public function create(Company $c): int
    {
        $this->db->insert($this->table(), [
            'name' => $c->name,
            'tax_code' => $c->tax_code,
            'address' => $c->address,
            'contact_person' => $c->contact_person,
            'phone' => $c->phone,
            'email' => $c->email,
            'created_at' => current_time('mysql'),
        ]);
        return (int)$this->db->insert_id;
    }
    public function update(Company $c): bool
    {
        return (bool)$this->db->update($this->table(), [
            'name' => $c->name,
            'tax_code' => $c->tax_code,
            'address' => $c->address,
            'contact_person' => $c->contact_person,
            'phone' => $c->phone,
            'email' => $c->email,
            'updated_at' => current_time('mysql'),
        ], ['id' => $c->id]);
    }
    public function find_by_id(int $id): ?Company
    {
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->table()} WHERE id=%d", $id));
        if (!$row) return null;
        return new Company((int)$row->id, $row->name, $row->tax_code, $row->address, $row->contact_person, $row->phone, $row->email);
    }
}
