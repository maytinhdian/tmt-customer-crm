<?php
// src/Domain/Entities/quotation.php
namespace TMT\CRM\Domain\Entities;

final class Quotation
{
    public function __construct(
        public ?int $id,
        public int $customer_id,
        public float $total,
        public string $status,
        public ?string $note = null,
    ) {}
}
