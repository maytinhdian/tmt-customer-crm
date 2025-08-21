<?php

namespace TMT\CRM\Application\Services;

use TMT\CRM\Application\DTO\CustomerDTO;
use TMT\CRM\Domain\Repositories\CustomerRepositoryInterface;

class CustomerService
{
    private CustomerRepositoryInterface $repo;

    public function __construct(CustomerRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function get_by_id(int $id): ?CustomerDTO
    {
        return $this->repo->find_by_id($id);
    }

    /**
     * @return array{items: CustomerDTO[], total: int, page: int, per_page: int}
     */
    public function list_customers(int $page = 1, int $per_page = 20, array $filters = []): array
    {
        $items = $this->repo->list_paginated($page, $per_page, $filters);
        $total = $this->repo->count_all($filters);

        return compact('items', 'total', 'page', 'per_page');
    }

    public function create(CustomerDTO $dto): int
    {
        $this->validate($dto, false);
        return $this->repo->create($dto);
    }

    public function update(CustomerDTO $dto): bool
    {
        $this->validate($dto, true);
        return $this->repo->update($dto);
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
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
        if ($dto->phone && !preg_match('/^[0-9+\-\s()]{6,20}$/', $dto->phone)) {
            throw new \InvalidArgumentException('Số điện thoại không hợp lệ.');
        }
    }
}




