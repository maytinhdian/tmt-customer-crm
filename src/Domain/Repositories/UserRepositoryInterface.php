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
}
