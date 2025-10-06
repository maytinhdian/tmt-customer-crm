<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Domain\Repositories;

interface NotificationPreferenceRepositoryInterface
{
    /**
     * Lấy tuỳ chọn thông báo của user cho 1 event + channel.
     * @return array|null ['id','user_id','event_name','channel','is_enabled','quiet_hours']
     */
    public function get_user_pref(int $user_id, string $event, string $channel): ?array;

    /**
     * Ghi/ cập nhật tuỳ chọn thông báo của user.
     */
    public function set_user_pref(int $user_id, string $event, string $channel, bool $enabled, ?string $quiet_hours = null): void;
}
