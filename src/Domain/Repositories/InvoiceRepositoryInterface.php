<?php
// src/Domain/Repositories/invoice-repository-interface.php
namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Domain\Entities\Invoice;

interface InvoiceRepositoryInterface {
    public function create(Invoice $i): int;
    public function update(Invoice $i): bool;
    public function find_by_id(int $id): ?Invoice;
}