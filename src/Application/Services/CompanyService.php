<?php

declare(strict_types=1);

namespace TMT\CRM\Application\Services;

use TMT\CRM\Application\DTO\CompanyDTO;
use TMT\CRM\Domain\Repositories\CompanyRepositoryInterface;

final class CompanyService
{
    private CompanyRepositoryInterface $repo;

    public function __construct(CompanyRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    /** Tạo mới công ty (validate + chống trùng MST) */
    public function create(array $data): int
    {
        $dto = $this->build_dto_from_array($data);
        $this->validate_required($dto);
        $this->ensure_unique_tax_code($dto->tax_code, null);
        return $this->repo->insert($dto);
    }

    /** Cập nhật công ty */
    public function update(int $id, array $data): bool
    {
        $dto = $this->build_dto_from_array($data, $id);
        $this->validate_required($dto);
        $this->ensure_unique_tax_code($dto->tax_code, $id);
        return $this->repo->update($dto);
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }

    public function find_by_id(int $id): ?CompanyDTO
    {
        return $this->repo->find_by_id($id);
    }

    /**
     * Trả về [items, total] để tiện cho WP_List_Table
     * @return array{items: CompanyDTO[], total: int}
     */
    public function get_paged(int $page, int $per_page, array $filters = []): array
    {
        $page     = max(1, $page);
        $per_page = max(1, $per_page);

        $items = $this->repo->list_paginated($page, $per_page, $filters);
        $total = $this->repo->count_all($filters);

        return ['items' => $items, 'total' => $total];
    }

    // ================== helpers ==================

    private function build_dto_from_array(array $data, ?int $id = null): CompanyDTO
    {
        $name     = trim((string)($data['name'] ?? ''));
        $tax_code = trim((string)($data['tax_code'] ?? ''));
        $address  = trim((string)($data['address'] ?? ''));

        return new CompanyDTO(
            $id,
            $name,
            $tax_code,
            $address,
            $this->nn($data['phone'] ?? null),
            $this->nn($data['email'] ?? null),
            $this->nn($data['website'] ?? null),
            $this->nn($data['note'] ?? null)
        );
    }

    private function validate_required(CompanyDTO $dto): void
    {
        $errors = [];
        if ($dto->name === '')     $errors[] = 'Tên công ty là bắt buộc.';
        if ($dto->tax_code === '') $errors[] = 'Mã số thuế là bắt buộc.';
        if ($dto->address === '')  $errors[] = 'Địa chỉ là bắt buộc.';


        // ✅ Kiểm tra MST Việt Nam
        if ($dto->tax_code !== '' && !$this->is_valid_vn_tax_code($dto->tax_code)) {
            $errors[] = 'Mã số thuế không hợp lệ (định dạng đúng: 10 số hoặc 10 số + "-XXX").';
        }

        if ($errors) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }
    }

    /**
     * Kiểm tra MST Việt Nam:
     * - 10 chữ số (tổ chức), hoặc
     * - 10 chữ số + "-" + 3 chữ số (đơn vị phụ thuộc), hoặc (tuỳ chọn)
     * - 13 chữ số liền (nếu muốn hỗ trợ nhập không có "-")
     */
    private function is_valid_vn_tax_code(string $tax_code): bool
    {
        $tax_code = trim($tax_code);

        // Nếu muốn CHỈ chấp nhận dạng có gạch: dùng pattern 1
        // $pattern = '/^\d{10}(-\d{3})?$/';

        // Nếu muốn cho phép cả 13 số liền: dùng pattern 2
        $pattern = '/^(?:\d{10}(?:-\d{3})?|\d{13})$/';

        return (bool) preg_match($pattern, $tax_code);
    }

    private function ensure_unique_tax_code(string $tax_code, ?int $exclude_id): void
    {
        $exists = $this->repo->find_by_tax_code($tax_code, $exclude_id);
        if ($exists) {
            throw new \RuntimeException('Mã số thuế đã tồn tại cho công ty khác.');
        }
    }

    private function nn(?string $v): ?string
    {
        $t = trim((string)$v);
        return $t !== '' ? $t : null;
    }
}
