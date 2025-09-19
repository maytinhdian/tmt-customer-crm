<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

interface PreferenceRepositoryInterface
{
    /**
     * Trả về mảng [channel => enabled(bool)] đã hợp nhất theo user (user > role > global)
     */
    public function resolve_for_user(int $user_id, string $event_key): array;
}
