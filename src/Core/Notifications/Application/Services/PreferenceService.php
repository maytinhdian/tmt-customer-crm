<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Application\Services;

/**
 * P0: luôn cho phép, để đơn giản hoá.
 * P1: bạn có thể đọc setting và kiểm tra allow(event_key, channel).
 */
final class PreferenceService
{
    public function allow(string $event_key, string $channel): bool
    {
        return true;
    }
}
