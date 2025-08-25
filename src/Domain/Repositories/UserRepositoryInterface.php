<?php

declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

interface UserRepositoryInterface
{
    /**
     * Trả về danh sách user có thể được gán làm "Người phụ trách".
     * Dạng: [ user_id => "Nhãn hiển thị" ].
     */
    public function get_assignable_owners(): array;
}
