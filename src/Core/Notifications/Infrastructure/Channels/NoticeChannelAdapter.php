<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Infrastructure\Channels;

use TMT\CRM\Core\Notifications\Application\Contracts\ChannelAdapterInterface;
use TMT\CRM\Core\Notifications\Domain\DTO\DeliveryDTO;

/**
 * Queue an admin notice for current user via transient.
 */
final class NoticeChannelAdapter implements ChannelAdapterInterface
{
    private static function transient_key(int $user_id): string
    {
        return 'tmt_crm_notices_' . $user_id;
    }

    public function send(DeliveryDTO $delivery, array $rendered): bool
    {
        if (!function_exists('get_current_user_id')) {
            return false;
        }
        $uid = (int)get_current_user_id();
        $key = self::transient_key($uid);
        $list = get_transient($key);
        if (!is_array($list)) { $list = []; }

        $list[] = [
            'type' => 'success',
            'message' => trim(($rendered['subject'] ?? '') . ' — ' . ($rendered['body'] ?? '')),
            'time' => time(),
        ];
        set_transient($key, $list, 60 * 60);
        return true;
    }
}
