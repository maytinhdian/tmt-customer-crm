<?php
// src/Domain/Entities/invoice.php
namespace TMT\CRM\Domain\Entities;

final class Invoice
{
    public function __construct(
        public ?int $id,
        public ?int $quotation_id,
        public int $customer_id,
        public float $total,
        public float $paid,
        public string $status,
    ) {}
}
