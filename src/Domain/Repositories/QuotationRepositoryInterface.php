<?php
// src/Domain/Repositories/quotation-repository-interface.php
namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Domain\Entities\Quotation;

interface QuotationRepositoryInterface {
    public function create(Quotation $q): int;
    public function find_by_id(int $id): ?Quotation;
    public function update_status(int $id, string $status): bool;
}