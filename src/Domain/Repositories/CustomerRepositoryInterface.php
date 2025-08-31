<?php

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Application\DTO\CustomerDTO;

interface CustomerRepositoryInterface
{
    public function find_by_id(int $id): ?CustomerDTO;

    /**
     * @return CustomerDTO[]
     */
    public function list_paginated(int $page, int $per_page, array $filters = []): array;

    public function count_all(array $filters = []): int;

    public function create(CustomerDTO $dto): int;     // return new id
    public function update(CustomerDTO $dto): bool;
    public function delete(int $id): bool;
    public function find_by_email_or_phone(?string $email = null, ?string $phone = null, ?int $exclude_id = null): ?CustomerDTO;
    public function get_owner_id(int $id): ?int;

    /**
     * Tìm kiếm dạng phân trang để đổ vào Select2.
     * Trả về mảng: ['items' => [ ['id'=>int, 'name'=>string], ... ], 'total' => int]
     */
    public function search_for_select(string $term, int $page, int $per_page = 20): array;

    /**
     * Lấy nhãn hiển thị từ id (dùng khi load giá trị ban đầu).
     * Trả về chuỗi tên hoặc null nếu không có.
     */
    public function find_name_by_id(int $id): ?string;
}
