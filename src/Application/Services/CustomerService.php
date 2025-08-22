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

        $items    = $this->repo->list_paginated($page, $args['limit'], $args);
        $total    = $this->repo->count_all($args);

        return ['items' => $items, 'total' => (int)$total];
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
        $re = '/^(?:\+?84|0)(?:3[2-9]|5[25689]|7[06-9]|8[1-9]|9[0-46-9])\d{7}$/';
        if ($dto->phone && !preg_match($re, $dto->phone)) {
            throw new \InvalidArgumentException('Số điện thoại không hợp lệ.');
        }
    }
}
