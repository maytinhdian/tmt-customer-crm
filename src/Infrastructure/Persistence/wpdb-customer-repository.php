<?php
namespace TMT\CRM\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Domain\Entities\Customer;
use TMT\CRM\Domain\Repositories\Customer_Repository_Interface;

final class WPDB_Customer_Repository implements Customer_Repository_Interface {
    public function __construct(private wpdb $db) {}
    private function table(): string { return $this->db->prefix . 'crm_customers'; }

    public function create(Customer $c): int {
        $this->db->insert($this->table(), [
            'full_name' => $c->full_name,
            'phone'     => $c->phone,
            'email'     => $c->email,
            'company_id'=> $c->company_id,
            'address'   => $c->address,
            'tags'      => $c->tags,
            'note'      => $c->note,
            'created_at'=> current_time('mysql'),
        ], ['%s','%s','%s','%d','%s','%s','%s','%s']);
        return (int) $this->db->insert_id;
    }

    public function update(Customer $c): bool {
        return (bool) $this->db->update($this->table(), [
            'full_name' => $c->full_name,
            'phone'     => $c->phone,
            'email'     => $c->email,
            'company_id'=> $c->company_id,
            'address'   => $c->address,
            'tags'      => $c->tags,
            'note'      => $c->note,
        ], ['id' => $c->id], ['%s','%s','%s','%d','%s','%s','%s'], ['%d']);
    }

    public function find_by_id(int $id): ?Customer {
        $row = $this->db->get_row($this->db->prepare(
            "SELECT * FROM {$this->table()} WHERE id = %d", $id
        ));
        if (!$row) return null;
        return new Customer((int)$row->id, $row->full_name, $row->phone, $row->email, (int)$row->company_id, $row->address, $row->tags, $row->note);
    }

    public function find_by_email_or_phone(string $email, string $phone): ?Customer {
        $row = $this->db->get_row($this->db->prepare(
            "SELECT * FROM {$this->table()} WHERE email = %s OR phone = %s LIMIT 1", $email, $phone
        ));
        if (!$row) return null;
        return new Customer((int)$row->id, $row->full_name, $row->phone, $row->email, (int)$row->company_id, $row->address, $row->tags, $row->note);
    }

    public function search(string $keyword, int $paged = 1, int $per_page = 20): array {
        $offset = ($paged - 1) * $per_page;
        $kw = '%' . $this->db->esc_like($keyword) . '%';
        $items = $this->db->get_results($this->db->prepare(
            "SELECT * FROM {$this->table()} WHERE full_name LIKE %s OR phone LIKE %s OR email LIKE %s ORDER BY id DESC LIMIT %d OFFSET %d",
            $kw, $kw, $kw, $per_page, $offset
        ));
        $total = (int) $this->db->get_var($this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table()} WHERE full_name LIKE %s OR phone LIKE %s OR email LIKE %s",
            $kw, $kw, $kw
        ));
        return [$items, $total];
    }

    public function delete(int $id): bool {
        return (bool) $this->db->delete($this->table(), ['id' => $id], ['%d']);
    }
}