<?php
namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Domain\Entities\Customer;

interface CustomerRepositoryInterface {
    public function create(Customer $customer): int;          // return inserted ID
    public function update(Customer $customer): bool;
    public function find_by_id(int $id): ?Customer;
    public function find_by_email_or_phone(string $email, string $phone): ?Customer;
    public function search(string $keyword, int $paged = 1, int $per_page = 20): array; // [items, total]
    public function delete(int $id): bool;
}
