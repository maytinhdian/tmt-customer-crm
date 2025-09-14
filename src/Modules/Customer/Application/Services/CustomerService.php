<?php

namespace TMT\CRM\Modules\Customer\Application\Services;

use TMT\CRM\Modules\Customer\Application\DTO\CustomerDTO;
use TMT\CRM\Modules\Customer\Application\DTO\EmploymentHistoryDTO;

use TMT\CRM\Modules\Customer\Domain\Repositories\CustomerRepositoryInterface;
use \TMT\CRM\Modules\Customer\Domain\Repositories\EmploymentHistoryRepositoryInterface;

class CustomerService
{
    // private CustomerRepositoryInterface $repo;

    public function __construct(
        private CustomerRepositoryInterface $customer_repo,
        private EmploymentHistoryRepositoryInterface $history_repo
    ) {}

    public function get_by_id(int $id): ?CustomerDTO
    {
        return $this->customer_repo->find_by_id($id);
    }



    /**
     * @return array{items: array<int, mixed>, total: int}
     */
    public function list_customers(int $page, int $perPage, array $filters = []): array
    {
        $page    = max(1, $page);
        $perPage = max(1, $perPage);

        // Whitelist orderby để chống SQL injection
        $allowedOrderby = ['id', 'name', 'email', 'phone', 'company'];
        $orderby = $filters['orderby'] ?? 'id';
        if (!in_array($orderby, $allowedOrderby, true)) {
            $orderby = 'id';
        }

        $order = strtoupper($filters['order'] ?? 'DESC');
        $order = ($order === 'ASC') ? 'ASC' : 'DESC';

        $args = [
            'keyword'  => (string)($filters['keyword'] ?? ''),
            'type'     => (string)($filters['type'] ?? ''),
            'owner_id' => $filters['owner_id'] ?? null,
            'orderby'  => $orderby,
            'order'    => $order,
            'limit'    => $perPage,
            'offset'   => ($page - 1) * $perPage,
        ];

        $items    = $this->customer_repo->list_paginated($page, $args['limit'], $args);
        $total    = $this->customer_repo->count_all($args);

        return ['items' => $items, 'total' => (int)$total];
    }

    public function create(CustomerDTO $dto): int
    {
        $this->validate($dto, false);
        return $this->customer_repo->create($dto);
    }

    public function update(int $id, CustomerDTO $dto): bool
    {
        $this->validate($dto, true);
        return $this->customer_repo->update($id, $dto);
    }

    public function delete(int $id): bool
    {
        return $this->customer_repo->delete($id);
    }

    public function list_paginated(int $page, int $per_page, array $filters = []): array
    {
        return $this->customer_repo->list_paginated($page, $per_page, $filters);
    }

    public function count_all(array $filters = []): int
    {
        return $this->customer_repo->count_all($filters);
    }

    public function get_current_company(int $customer_id): ?EmploymentHistoryDTO
    {
        return $this->history_repo->find_current_company_of_customer($customer_id);
    }

    private function validate(CustomerDTO $dto, bool $is_update): void
    {
        if ($is_update && !$dto->id) {
            throw new \InvalidArgumentException('Missing id for update.');
        }
        $name = trim($dto->name ?? '');
        if ($name === '') {
            throw new \InvalidArgumentException('Tên khách hàng là bắt buộc.');
        }
        if ($dto->email && !is_email($dto->email)) {
            throw new \InvalidArgumentException('Email không hợp lệ.');
        }
        $re = '/^(?:\+?84|0)(?:3[2-9]|5[25689]|7[06-9]|8[1-9]|9[0-46-9])\d{7}$/';
        if ($dto->phone && !preg_match($re, $dto->phone)) {
            throw new \InvalidArgumentException('Số điện thoại không hợp lệ.');
        }
        // ✅ Kiểm tra trùng email hoặc phone
        if ($dto->email || $dto->phone) {
            $dup = $this->customer_repo->find_by_email_or_phone(
                $dto->email,
                $dto->phone,
                $is_update ? (int)$dto->id : null
            );

            if ($dup) {
                throw new \RuntimeException('Email hoặc số điện thoại đã tồn tại.');
            }
        }
    }
}
