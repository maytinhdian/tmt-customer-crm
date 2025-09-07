<?php

declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Application\DTO\CompanyDTO;

interface CompanyRepositoryInterface
{
    public function find_by_id(int $id): ?CompanyDTO;

    public function find_name_by_id(int $id): ?string;

    // public function find_by_tax_code(string $tax_code, ?int $exclude_id = null): ?CompanyDTO;

    /**
     * @return CompanyDTO[]
     */
    public function list_paginated(int $page, int $per_page, array $filters = []): array;

    public function count_all(array $filters = []): int;
    public function insert(CompanyDTO $dto): int;   // return new ID
    public function update(CompanyDTO $dto): bool;
    public function delete(int $id): bool;
    
    /**
     * Tìm kiếm công ty cho Select2 (phân trang đơn giản)
     * @return array{items: array<array{id:int,name:string}>, total:int}
    */
    public function search_for_select(string $keyword, int $page, int $per_page = 20): array;
    

    /** Lấy owner_id của công ty. Không ném exception khi không có. */
    // public function get_owner_id(int $company_id): ?int;
}
