<?php
namespace TMT\CRM\Domain\Repositories;

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
