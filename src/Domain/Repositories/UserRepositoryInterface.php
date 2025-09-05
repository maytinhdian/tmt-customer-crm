<?php

declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

interface UserRepositoryInterface
{
    /**
     * Tìm user cho Select2, có lọc theo capability bắt buộc.
     * @return array{items: array<array{id:int,label:string}>, more: bool}
     */
    public function search_for_select(
        string $keyword,
        int $page,
        int $per_page,
        string $must_capability
    ): array;

    /**
     * Lấy nhãn hiển thị (label) theo ID (để preload Select2).
     */
    public function find_label_by_id(int $user_id): ?string;
    
    /** Lấy tên hiển thị của user (display_name hoặc user_login). */
    public function get_display_name(int $user_id): ?string;

        /**
     * Trả về map [id => UserDTO]
     * @param int[] $ids
     * @return array<int, UserDTO>
     */
    public function find_by_ids(array $ids): array;
}
