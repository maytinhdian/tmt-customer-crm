<?php
// src/Infrastructure/Persistence/wpdb-payment-repository.php
namespace TMT\CRM\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Domain\Repositories\PaymentRepositoryInterface;

final class WpdbPaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(private wpdb $db) {}
    private function table(): string
    {
        return $this->db->prefix . 'crm_payments';
    }

    public function create(int $invoice_id, float $amount, ?string $note = null): int
    {
        $this->db->insert($this->table(), [
            'invoice_id' => $invoice_id,
            'amount' => $amount,
            'note' => $note,
            'paid_at' => current_time('mysql'),
            'created_at' => current_time('mysql'),
        ]);
        return (int)$this->db->insert_id;
    }
    public function find_by_invoice(int $invoice_id): array
    {
        return $this->db->get_results($this->db->prepare(
            "SELECT * FROM {$this->table()} WHERE invoice_id=%d ORDER BY paid_at DESC",
            $invoice_id
        ));
    }
}
