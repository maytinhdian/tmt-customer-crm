<?php
// src/Domain/Repositories/payment-repository-interface.php
namespace TMT\CRM\Domain\Repositories;

interface Payment_Repository_Interface
{
    public function create(int $invoice_id, float $amount, ?string $note = null): int;
    public function find_by_invoice(int $invoice_id): array; // list bản ghi
}
