<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Core\Capabilities\Domain\Repositories;

/**
 * Lưu/đọc ma trận quyền theo vai trò (role).
 * Định dạng lưu trữ gợi ý: array<string role, string[] caps>
 */
interface CapabilitiesRepositoryInterface
{
    /** Lấy toàn bộ ma trận role => [capability,...] */
    public function get_matrix(): array;

    /** Ghi đè toàn bộ ma trận */
    public function set_matrix(array $matrix): void;
}
