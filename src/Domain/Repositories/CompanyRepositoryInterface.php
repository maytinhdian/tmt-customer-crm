<?php
namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Application\DTO\CompanyDTO;
use TMT\CRM\Domain\Entities\Company;

interface CompanyRepositoryInterface
{
    public function install(): void;

    public function find_by_id(int $id): ?Company;

    /** @return array{items: Company[], total:int} */
    public function search(string $keyword = '', int $page = 1, int $perPage = 20): array;

    public function insert(CompanyDTO $dto): int; // return new id

    public function update(CompanyDTO $dto): bool;

    public function delete(int $id): bool;

    /** Chống trùng tên+MST/điện thoại/email (trong tạo/sửa) */
    public function find_duplicate(?int $excludeId, ?string $name, ?string $taxCode, ?string $phone, ?string $email): ?Company;
}
