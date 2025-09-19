<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Application\Services;

final class PolicyGuard
{
    /** Kiểm tra quyền xem entity trước khi gửi tới người nhận */
    public static function can_receive(string $entity_type, int $entity_id, int $user_id): bool
    {
        // TODO: gọi PolicyService khi đã sẵn sàng
        return true;
    }
}
