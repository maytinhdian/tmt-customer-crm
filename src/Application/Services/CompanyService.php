<?php

declare(strict_types=1);

namespace TMT\CRM\Application\Services;

use TMT\CRM\Application\DTO\CompanyDTO;
use TMT\CRM\Application\DTO\CompanyContactDTO;

use TMT\CRM\Domain\Repositories\CompanyRepositoryInterface;
use TMT\CRM\Domain\Repositories\CompanyContactRepositoryInterface;



final class CompanyService
{
    public const ROLE_ACCOUNTING = 'accounting';
    public const ROLE_PURCHASING = 'purchasing';
    public const ROLE_INVOICE    = 'invoice';


    public function __construct(
        private CompanyRepositoryInterface $company_repo,
        private CompanyContactRepositoryInterface $contact_repo
    ) {}


    /** Tạo mới công ty (validate + chống trùng MST) */
    public function create(array $data): int
    {
        $dto = $this->build_dto_from_array($data);
        $this->validate_required($dto);
        $this->ensure_unique_tax_code($dto->tax_code, null);
        return $this->company_repo->insert($dto);
    }

    /** Cập nhật công ty */
    public function update(int $id, array $data): bool
    {
        $dto = $this->build_dto_from_array($data, $id);
        $this->validate_required($dto);
        $this->ensure_unique_tax_code($dto->tax_code, $id);
        return $this->company_repo->update($dto);
    }

    public function delete(int $id): bool
    {
        return $this->company_repo->delete($id);
    }

    public function find_by_id(int $id): ?CompanyDTO
    {
        return $this->company_repo->find_by_id($id);
    }

    /**
     * Trả về [items, total] để tiện cho WP_List_Table
     * @return array{items: CompanyDTO[], total: int}
     */
    public function get_paged(int $page, int $per_page, array $filters = []): array
    {
        $page     = max(1, $page);
        $per_page = max(1, $per_page);

        $items = $this->company_repo->list_paginated($page, $per_page, $filters);
        $total = $this->company_repo->count_all($filters);

        return ['items' => $items, 'total' => $total];
    }


    public function get_active_contact_by_role(int $company_id, string $role): ?CompanyContactDTO
    {
        return $this->contact_repo->get_primary_contact($company_id, $role);
    }

    public function get_active_contacts(int $company_id, ?string $role = null): array
    {
        return $this->contact_repo->find_active_contacts_by_company($company_id, $role);
    }

    public function assign_contact(
        int $company_id,
        int $customer_id,
        string $role,
        ?string $title = null,
        bool $is_primary = false,
        ?string $start_date = null,
        ?string $note = null
    ): int {
        if ($is_primary) {
            $this->contact_repo->clear_primary_for_role($company_id, $role);
        }

        $dto = new CompanyContactDTO(
            id: null,
            company_id: $company_id,
            customer_id: $customer_id,
            role: $role,
            title: $title,
            is_primary: $is_primary,
            start_date: $start_date,
            end_date: null,
            note: $note,
            created_at: current_time('mysql'),
            updated_at: current_time('mysql')
        );

        return $this->contact_repo->upsert($dto);
    }

    public function set_primary_contact(int $contact_id): bool
    {
        $contact = $this->contact_repo->find_by_id($contact_id);
        if (!$contact) return false;

        $this->contact_repo->clear_primary_for_role($contact->company_id, $contact->role);
        $contact->is_primary = true;
        $contact->updated_at = current_time('mysql');
        $this->contact_repo->upsert($contact);
        return true;
    }

    public function end_contact(int $contact_id, string $end_date): bool
    {
        return $this->contact_repo->end_contact($contact_id, $end_date);
    }

    // ================== helpers ==================

    private function build_dto_from_array(array $data, ?int $id = null): CompanyDTO
    {
        $name     = trim((string)($data['name'] ?? ''));
        $tax_code = trim((string)($data['tax_code'] ?? ''));
        $address  = trim((string)($data['address'] ?? ''));
        $owner_id = isset($data['owner_id']) ? (int)$data['owner_id'] : 0;
        $owner_id = $owner_id > 0 ? $owner_id : null;
        
        return new CompanyDTO(
            $id,
            $name,
            $tax_code,
            $address,
            $this->nn($data['phone'] ?? null),
            $this->nn($data['email'] ?? null),
            $this->nn($data['website'] ?? null),
            $this->nn($data['note'] ?? null),
            $this->$owner_id,                               // ⬅️
            $this->nn($data['representer'] ?? null)       // ⬅️
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
        $exists = $this->company_repo->find_by_tax_code($tax_code, $exclude_id);
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
