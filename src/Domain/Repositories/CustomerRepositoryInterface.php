<?php
namespace TMT\CRM\Domain\Repositories;

// use TMT\CRM\Domain\Entities\Customer;

// interface CustomerRepositoryInterface {
//     public function create(Customer $customer): int;          // return inserted ID
//     public function update(Customer $customer): bool;
//     public function find_by_id(int $id): ?Customer;
//     public function find_by_email_or_phone(string $email, string $phone): ?Customer;
//     public function search(string $keyword, int $paged = 1, int $per_page = 20): array; // [items, total]
//     public function delete(int $id): bool;
// }


// <?php

// namespace TMT\CRM\Domain\Repository;

use TMT\CRM\Application\DTO\CustomerDTO;

interface CustomerRepositoryInterface
{
    public function find_by_id(int $id): ?CustomerDTO;

    /**
     * @return CustomerDTO[]
     */
    public function list_paginated(int $page, int $per_page, array $filters = []): array;

    public function count_all(array $filters = []): int;

    public function create(CustomerDTO $dto): int;     // return new id
    public function update(CustomerDTO $dto): bool;
    public function delete(int $id): bool;
}
