<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Files\Application\Services;

use TMT\CRM\Core\Files\Application\DTO\FileDTO;
use TMT\CRM\Shared\Infrastructure\Security\Capability;

/**
 * Kiểm soát quyền cho thao tác tệp.
 * - Dùng Capability (core) làm chốt kiểm tra.
 * - Có thể mở rộng thêm rule theo subject (owner, role dự án, v.v.).
 */
final class PolicyService
{
    public function ensure_can_upload(int $user_id, string $subject_type, int $subject_id): void
    {
        Capability::require(Capability::FILE_UPLOAD);
    }

    public function ensure_can_update(int $user_id, FileDTO $file): void
    {
        Capability::require(Capability::FILE_UPDATE);
    }

    public function ensure_can_delete(int $user_id, FileDTO $file): void
    {
        Capability::require(Capability::FILE_DELETE);
    }

    public function ensure_can_restore(int $user_id, FileDTO $file): void
    {
        Capability::require(Capability::FILE_RESTORE);
    }

    public function ensure_can_view(int $user_id, FileDTO $file): void
    {
        Capability::require(Capability::FILE_READ);
    }
}
