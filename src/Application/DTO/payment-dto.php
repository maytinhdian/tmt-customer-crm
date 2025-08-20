<?php
// src/Application/DTO/payment-dto.php
namespace TMT\CRM\Application\DTO;

final class Payment_DTO
{
    public function __construct(
        public int $invoice_id,
        public float $amount,
        public ?string $note = null,
    ) {}
}
