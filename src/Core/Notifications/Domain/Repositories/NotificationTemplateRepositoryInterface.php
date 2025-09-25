<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Domain\Repositories;

interface NotificationTemplateRepositoryInterface
{
    /**
     * Tìm template theo code (ưu tiên is_active=1 ở tầng implementation nếu cần).
     */
    public function find_by_code(string $code): ?array;

    /**
     * Tạo hoặc cập nhật template theo 'code'.
     * @param array $tpl ['code','channel','subject_tpl','body_tpl','is_active']
     * @return int ID template
     */
    public function upsert(array $tpl): int;

    /**
     * Danh sách template có filter & phân trang.
     * @param array $filters ['channel' => string, 'is_active' => int]
     * @return array<array<string,mixed>>
     */
    public function list(array $filters = [], int $page = 1, int $per_page = 20): array;
}
