<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Application\Services;

final class PreferenceService
{
    /**
     * Trả về danh sách kênh bật cho 1 event.
     * P0: đọc option nếu có, không thì mặc định ['admin_notice'].
     * @return string[]
     */
    public function channels_for(string $event_key): array
    {
        // Nếu chưa có settings, trả mặc định
        if (!function_exists('get_option')) {
            return ['admin_notice'];
        }

        $opt = get_option('tmt_crm_notifications', []);
        $channels = $opt['events'][$event_key]['channels'] ?? null;

        if (is_array($channels) && $channels) {
            // Chuẩn hóa chuỗi trống/ khoảng trắng
            return array_values(array_filter(array_map('strval', $channels), static fn($c) => $c !== ''));
        }

        // Mặc định P0
        return ['admin_notice'];
    }

    /** Cho phép gửi event theo channel không? */
    public function allow(string $event_key, string $channel): bool
    {
        // return true;
        return in_array($channel, $this->channels_for($event_key), true);
    }
}
