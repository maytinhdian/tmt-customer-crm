<?php

declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Application\DTO\CompanyDTO;

interface CompanyRepositoryInterface
{
    public function find_by_id(int $id): ?CompanyDTO;

    /**
     * @return CompanyDTO[]
     */
    public function list_paginated(int $page, int $per_page, array $filters = []): array;

    public function count_all(array $filters = []): int;

    public function find_by_tax_code(string $tax_code, ?int $exclude_id = null): ?CompanyDTO;

    public function insert(CompanyDTO $dto): int;   // return new ID
    public function update(CompanyDTO $dto): bool;
    public function delete(int $id): bool;
}
