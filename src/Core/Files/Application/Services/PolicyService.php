<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Files\Application\Services;

use TMT\CRM\Core\Files\Domain\DTO\FileDTO;

final class PolicyService
{
    public const CAP_READ    = 'tmt_crm_file_read';
    public const CAP_CREATE  = 'tmt_crm_file_create';
    public const CAP_DELETE  = 'tmt_crm_file_delete';
    public const CAP_RESTORE = 'tmt_crm_file_restore';

    public static function canRead(int $currentUserId, FileDTO $file): bool
    {
        // Owner can read if private; otherwise require capability
        if ($file->uploadedBy === $currentUserId) {
            return true;
        }
        return current_user_can(self::CAP_READ);
    }

    public static function canCreate(int $currentUserId, string $entityType, int $entityId): bool
    {
        return current_user_can(self::CAP_CREATE);
    }

    public static function canDelete(int $currentUserId, FileDTO $file): bool
    {
        if ($file->uploadedBy === $currentUserId) {
            return true;
        }
        return current_user_can(self::CAP_DELETE);
    }

    public static function canRestore(int $currentUserId, FileDTO $file): bool
    {
        return current_user_can(self::CAP_RESTORE);
    }
}
