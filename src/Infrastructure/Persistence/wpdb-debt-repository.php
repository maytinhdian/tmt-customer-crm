<?php
// src/Infrastructure/Persistence/wpdb-debt-repository.php
namespace TMT\CRM\Infrastructure\Persistence;

use wpdb; use TMT\CRM\Domain\Repositories\Debt_Repository_Interface;

final class WPDB_Debt_Repository implements Debt_Repository_Interface {
    public function __construct(private wpdb $db) {}
    private function table(): string { return $this->db->prefix.'crm_debts'; }

    public function mark_paid(int $debt_id): bool {
        return (bool)$this->db->update($this->table(), [
            'paid' => 1,
            'updated_at' => current_time('mysql'),
        ], ['id' => $debt_id]);
    }
}