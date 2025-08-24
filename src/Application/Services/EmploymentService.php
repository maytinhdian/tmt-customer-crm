<?php

declare(strict_types=1);

namespace TMT\CRM\Application\Services;

use TMT\CRM\Application\DTO\CustomerEmploymentDTO;
use TMT\CRM\Domain\Repositories\EmploymentRepositoryInterface;
use TMT\CRM\Domain\Repositories\CompanyRepositoryInterface;
use TMT\CRM\Domain\Repositories\CustomerRepositoryInterface;

/**
 * EmploymentService
 *
 * Quản lý lịch sử làm việc của khách hàng (customer) tại các công ty theo thời gian.
 * - Tạo/đóng employment
 * - Lấy employment đang active
 * - Liệt kê lịch sử theo customer, danh sách đang active theo company
 *
 * Lưu ý:
 * - Hàm dùng snake_case theo quy ước.
 */
final class EmploymentService
{
    private EmploymentRepositoryInterface $repo;
    private CompanyRepositoryInterface $company_repo;
    private CustomerRepositoryInterface $customer_repo;

    public function __construct(
        EmploymentRepositoryInterface $repo,
        CompanyRepositoryInterface $company_repo,
        CustomerRepositoryInterface $customer_repo
    ) {
        $this->repo          = $repo;
        $this->company_repo  = $company_repo;
        $this->customer_repo = $customer_repo;
    }

    /**
     * Tạo employment mới (mặc định is_primary = true nếu không truyền).
     * $payload: [
     *   'customer_id' (int, bắt buộc),
     *   'company_id'  (int, bắt buộc),
     *   'start_date'  ('Y-m-d', nếu không có -> hôm nay),
     *   'end_date'    ('Y-m-d' | null),
     *   'is_primary'  (bool|0|1|'true'|'false')
     * ]
     */
    public function create(array $payload): int
    {
        $errors = $this->validate_create_payload($payload);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Invalid employment data: ' . implode('; ', $errors));
        }

        // Check tồn tại customer & company
        $customer = $this->customer_repo->find_by_id((int)$payload['customer_id']);
        if (!$customer) {
            throw new \RuntimeException('Customer not found #' . (int)$payload['customer_id']);
        }
        $company = $this->company_repo->find_by_id((int)$payload['company_id']);
        if (!$company) {
            throw new \RuntimeException('Company not found #' . (int)$payload['company_id']);
        }

        // Dựng DTO từ mảng (đã có from_array)
        $dto = CustomerEmploymentDTO::from_array([
            'customer_id' => (int)$payload['customer_id'],
            'company_id'  => (int)$payload['company_id'],
            'start_date'  => (string)($payload['start_date'] ?? date('Y-m-d')),
            'end_date'    => $payload['end_date'] ?? null,
            'is_primary'  => $payload['is_primary'] ?? 1,
        ]);

        return $this->repo->create($dto);
    }

    /**
     * Đóng employment (set end_date).
     */
    public function close_employment(int $employment_id, string $end_date): bool
    {
        if (!$this->is_valid_date($end_date)) {
            throw new \InvalidArgumentException('Invalid end_date format (Y-m-d expected)');
        }
        return $this->repo->close_employment($employment_id, $end_date);
    }

    /**
     * Employment đang active (end_date IS NULL) của một customer.
     */
    public function get_active_by_customer(int $customer_id): ?CustomerEmploymentDTO
    {
        return $this->repo->get_active_by_customer($customer_id);
    }

    /**
     * Lịch sử theo customer (mới → cũ).
     */
    public function list_by_customer(int $customer_id): array
    {
        return $this->repo->list_by_customer($customer_id);
    }

    /**
     * Danh sách người đang làm tại 1 công ty.
     */
    public function list_active_by_company(int $company_id): array
    {
        return $this->repo->list_active_by_company($company_id);
    }

    /**
     * Di chuyển customer sang công ty khác (không xử lý role ở đây):
     * - Nếu đang active ở công ty A: end_date = (move_date - 1).
     * - Tạo employment mới ở công ty B: start_date = move_date.
     */
    public function move_customer_company(int $customer_id, int $to_company_id, string $move_date): bool
    {
        if (!$this->is_valid_date($move_date)) {
            throw new \InvalidArgumentException('Invalid move_date format (Y-m-d expected)');
        }

        // Validate entities
        $customer = $this->customer_repo->find_by_id($customer_id);
        if (!$customer) {
            throw new \RuntimeException("Customer #{$customer_id} not found");
        }
        $to_company = $this->company_repo->find_by_id($to_company_id);
        if (!$to_company) {
            throw new \RuntimeException("Company #{$to_company_id} not found");
        }

        // Close active employment if exists
        $active = $this->repo->get_active_by_customer($customer_id);
        if ($active) {
            $end_date = date('Y-m-d', strtotime($move_date . ' -1 day'));
            $this->repo->close_employment((int)$active->id, $end_date);
        }

        // Create new employment
        $dto = CustomerEmploymentDTO::from_array([
            'customer_id' => $customer_id,
            'company_id'  => $to_company_id,
            'start_date'  => $move_date,
            'end_date'    => null,
            'is_primary'  => 1,
        ]);
        $this->repo->create($dto);

        return true;
    }

    /* ========================== Helpers ========================== */

    private function validate_create_payload(array $p): array
    {
        $err = [];
        if (empty($p['customer_id'])) {
            $err[] = 'customer_id is required';
        }
        if (empty($p['company_id'])) {
            $err[] = 'company_id is required';
        }
        if (isset($p['start_date']) && !$this->is_valid_date((string)$p['start_date'])) {
            $err[] = 'start_date must be Y-m-d';
        }
        if (isset($p['end_date']) && $p['end_date'] !== null && $p['end_date'] !== '' && !$this->is_valid_date((string)$p['end_date'])) {
            $err[] = 'end_date must be Y-m-d or null';
        }
        return $err;
    }

    private function is_valid_date(string $d): bool
    {
        $t = \DateTime::createFromFormat('Y-m-d', $d);
        return $t && $t->format('Y-m-d') === $d;
    }
}
