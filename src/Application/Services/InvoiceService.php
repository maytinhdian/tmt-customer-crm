<?php
namespace TMT\CRM\Application\Services;

use TMT\CRM\Domain\Entities\Invoice;
use TMT\CRM\Domain\Repositories\InvoiceRepositoryInterface;

final class InvoiceService {
    const STATUS_UNPAID  = 'unpaid';
    const STATUS_PARTIAL = 'partial';
    const STATUS_PAID    = 'paid';

    public function __construct(private InvoiceRepositoryInterface $repo) {}

    public function create(int $customer_id, float $total): int {
        $invoice = new Invoice(null, $customer_id, $total, 0, self::STATUS_UNPAID);
        return $this->repo->create($invoice);
    }

    public function add_payment(int $invoice_id, float $amount): bool {
        $invoice = $this->repo->find_by_id($invoice_id);
        if (!$invoice) return false;
        $new_paid = $invoice->paid + $amount;
        $status = $new_paid >= $invoice->total ? self::STATUS_PAID : self::STATUS_PARTIAL;
        return $this->repo->update_payment($invoice_id, $new_paid, $status);
    }
}