<?php
// src/Infrastructure/Persistence/wpdb-invoice-repository.php
namespace TMT\CRM\Infrastructure\Persistence;

use wpdb; use TMT\CRM\Domain\Entities\Invoice; use TMT\CRM\Domain\Repositories\Invoice_Repository_Interface;

final class WPDB_Invoice_Repository implements Invoice_Repository_Interface {
    public function __construct(private wpdb $db) {}
    private function table(): string { return $this->db->prefix.'crm_invoices'; }

    public function create(Invoice $i): int {
        $this->db->insert($this->table(), [
            'quotation_id' => $i->quotation_id,
            'customer_id' => $i->customer_id,
            'total' => $i->total,
            'paid' => $i->paid,
            'status' => $i->status,
            'created_at' => current_time('mysql'),
        ]);
        return (int)$this->db->insert_id;
    }
    public function update(Invoice $i): bool {
        return (bool)$this->db->update($this->table(), [
            'quotation_id' => $i->quotation_id,
            'customer_id' => $i->customer_id,
            'total' => $i->total,
            'paid' => $i->paid,
            'status' => $i->status,
            'updated_at' => current_time('mysql'),
        ], ['id' => $i->id]);
    }
    public function find_by_id(int $id): ?Invoice {
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->table()} WHERE id=%d", $id));
        if (!$row) return null;
        return new Invoice((int)$row->id, $row->quotation_id ? (int)$row->quotation_id : null, (int)$row->customer_id, (float)$row->total, (float)$row->paid, $row->status);
    }
}