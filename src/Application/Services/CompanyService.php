<?php

declare(strict_types=1);

namespace TMT\CRM\Application\Services;

use TMT\CRM\Application\DTO\CompanyDTO;
use TMT\CRM\Application\DTO\CompanyContactRoleDTO;
use TMT\CRM\Application\DTO\CustomerEmploymentDTO;

use TMT\CRM\Domain\Repositories\CompanyRepositoryInterface;
use TMT\CRM\Domain\Repositories\CompanyContactRoleRepositoryInterface;
use TMT\CRM\Domain\Repositories\EmploymentRepositoryInterface;
use TMT\CRM\Domain\Repositories\CustomerRepositoryInterface;

final class CompanyService
{
    public const ROLE_ACCOUNTING = 'accounting';
    public const ROLE_PURCHASING = 'purchasing';
    public const ROLE_INVOICE    = 'invoice';

    private CompanyRepositoryInterface $company_repo;
    private CompanyContactRoleRepositoryInterface $role_repo;
    private EmploymentRepositoryInterface $employment_repo;
    private CustomerRepositoryInterface $customer_repo;

    public function __construct(
        CompanyRepositoryInterface $company_repo,
        CompanyContactRoleRepositoryInterface $role_repo,
        EmploymentRepositoryInterface $employment_repo,
        CustomerRepositoryInterface $customer_repo
    ) {
        $this->company_repo    = $company_repo;
        $this->role_repo       = $role_repo;
        $this->employment_repo = $employment_repo;
        $this->customer_repo   = $customer_repo;
    }


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


    /* ============================================================
     * 2) Vai trò liên hệ (company_contact_roles)
     * ============================================================ */

    /**
     * Gán 1 liên hệ vào 1 role tại công ty, hiệu lực từ $start_date (mặc định hôm nay).
     * - Kiểm tra tồn tại Company/Customer.
     * - Kiểm tra customer đang active tại company (employment end_date IS NULL).
     * - Tự end role cũ cùng (company, role) nếu có (end_date = hôm qua).
     * Trả về role_id mới.
     */
    public function assign_contact_role(
        int $company_id,
        int $customer_id,
        string $role,
        ?string $start_date = null
    ): int {
        $start_date = $start_date ?: date('Y-m-d');
        $this->assert_company_exists($company_id);
        $this->assert_customer_exists($customer_id);
        $this->assert_customer_active_at_company($customer_id, $company_id);

        // Kết thúc vai trò cũ (nếu có)
        $current = $this->role_repo->get_active_by_role($company_id, $role);
        if ($current && isset($current['id'])) {
            $yesterday = date('Y-m-d', strtotime($start_date . ' -1 day'));
            $this->role_repo->end_role((int)$current['id'], $yesterday);
        }

        // Tạo vai trò mới
        $dto = new CompanyContactRoleDTO();
        $dto->company_id  = $company_id;
        $dto->customer_id = $customer_id;
        $dto->role        = $role;
        $dto->start_date  = $start_date;
        $dto->end_date    = null;

        return $this->role_repo->assign_role($dto);
    }

    /** Kết thúc một vai trò (set end_date) */
    public function end_contact_role(int $role_id, string $end_date): bool
    {
        $this->assert_valid_date($end_date);
        return $this->role_repo->end_role($role_id, $end_date);
    }

    /**
     * Liên hệ đang đảm nhiệm 1 role tại công ty.
     * Return: ['id'=>role_id,'role'=>..., 'customer'=>['id','name','phone','email']] | null
     */
    public function get_active_contact_by_role(int $company_id, string $role): ?array
    {
        return $this->role_repo->get_active_by_role($company_id, $role);
    }

    /** Danh sách toàn bộ role đang active tại công ty (mỗi item như trên) */
    public function list_active_contacts(int $company_id): array
    {
        return $this->role_repo->list_active($company_id);
    }

    /* ============================================================
     * 3) Employment helpers (chuyển công ty)
     * ============================================================ */

    /**
     * Chuyển 1 customer sang công ty khác:
     * - Đóng employment hiện tại (end_date = move_date - 1).
     * - Tạo employment mới (start_date = move_date).
     * - Tuỳ chọn: đóng các role đang đảm nhiệm tại công ty cũ vào ngày trước khi chuyển.
     */
    public function move_customer_company(
        int $customer_id,
        int $to_company_id,
        string $move_date,
        bool $close_old_roles = true
    ): bool {
        $this->assert_valid_date($move_date);
        $this->assert_customer_exists($customer_id);
        $this->assert_company_exists($to_company_id);

        $active = $this->employment_repo->get_active_by_customer($customer_id);
        if ($active) {
            $end_date = date('Y-m-d', strtotime($move_date . ' -1 day'));
            $this->employment_repo->close_employment((int)$active->id, $end_date);
        }

        $new = new CustomerEmploymentDTO();
        $new->customer_id = $customer_id;
        $new->company_id  = $to_company_id;
        $new->start_date  = $move_date;
        $new->end_date    = null;
        $new->is_primary  = true;
        $this->employment_repo->create($new);

        if ($close_old_roles && $active) {
            $roles    = $this->role_repo->list_active((int)$active->company_id);
            $end_date = date('Y-m-d', strtotime($move_date . ' -1 day'));
            foreach ($roles as $r) {
                if ((int)($r['customer']['id'] ?? 0) === $customer_id) {
                    $this->role_repo->end_role((int)$r['id'], $end_date);
                }
            }
        }

        return true;
    }


    /* ============================================================
     * 4) Validation & internal asserts
     * ============================================================ */

    private function validate_company_payload(array $payload): array
    {
        $errors = [];

        $name = trim((string)($payload['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'Tên công ty bắt buộc';
        }

        if (isset($payload['tax_code'])) {
            $tax = trim((string)$payload['tax_code']);
            if ($tax !== '' && strlen($tax) > 64) {
                $errors[] = 'Mã số thuế quá dài (<= 64 ký tự)';
            }
        }

        if (isset($payload['address'])) {
            $addr = trim((string)$payload['address']);
            if ($addr !== '' && strlen($addr) > 255) {
                $errors[] = 'Địa chỉ quá dài (<= 255 ký tự)';
            }
        }

        return $errors;
    }

    private function assert_valid_date(string $d): void
    {
        $t = \DateTime::createFromFormat('Y-m-d', $d);
        if (!$t || $t->format('Y-m-d') !== $d) {
            throw new \InvalidArgumentException('Invalid date format (Y-m-d expected)');
        }
    }

    private function assert_company_exists(int $company_id): void
    {
        if (!$this->company_repo->find_by_id($company_id)) {
            throw new \RuntimeException("Company #{$company_id} not found");
        }
    }

    private function assert_customer_exists(int $customer_id): void
    {
        if (!$this->customer_repo->find_by_id($customer_id)) {
            throw new \RuntimeException("Customer #{$customer_id} not found");
        }
    }

    private function assert_customer_active_at_company(int $customer_id, int $company_id): void
    {
        $active = $this->employment_repo->get_active_by_customer($customer_id);
        if (!$active || $active->company_id !== $company_id) {
            throw new \RuntimeException("Customer #{$customer_id} is not active at company #{$company_id}");
        }
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
