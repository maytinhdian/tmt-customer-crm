<?php
// src/Infrastructure/Persistence/wpdb-quotation-repository.php
namespace TMT\CRM\Infrastructure\Persistence;

use wpdb; use TMT\CRM\Domain\Entities\Quotation; use TMT\CRM\Domain\Repositories\QuotationRepositoryInterface;

final class WpdbQuotationRepository implements QuotationRepositoryInterface {
    public function __construct(private wpdb $db) {}
    private function table(): string { return $this->db->prefix.'crm_quotations'; }

    public function create(Quotation $q): int {
        $this->db->insert($this->table(), [
            'customer_id' => $q->customer_id,
            'total' => $q->total,
            'status' => $q->status,
            'note' => $q->note,
            'created_at' => current_time('mysql'),
        ]);
        return (int)$this->db->insert_id;
    }
    public function find_by_id(int $id): ?Quotation {
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->table()} WHERE id=%d", $id));
        if (!$row) return null;
        return new Quotation((int)$row->id, (int)$row->customer_id, (float)$row->total, $row->status, $row->note);
    }
    public function update_status(int $id, string $status): bool {
        return (bool)$this->db->update($this->table(), [
            'status' => $status,
            'updated_at' => current_time('mysql'),
        ], ['id' => $id]);
    }
}