<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Infrastructure\Persistence;

use TMT\CRM\Core\Notifications\Domain\Repositories\NotificationPreferenceRepositoryInterface;
use wpdb;

final class WpdbNotificationPreferenceRepository implements NotificationPreferenceRepositoryInterface
{
    public function __construct(private wpdb $db) {}

    public function get_user_pref(int $user_id, string $event, string $channel): ?array
    {
        $table = $this->db->prefix . 'tmt_notification_preferences';
        $sql   = $this->db->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d AND event_name = %s AND channel = %s LIMIT 1",
            $user_id,
            $event,
            $channel
        );
        $row = $this->db->get_row($sql, ARRAY_A);

        return $row ?: null;
    }

    public function set_user_pref(int $user_id, string $event, string $channel, bool $enabled, ?string $quiet_hours = null): void
    {
        $table = $this->db->prefix . 'tmt_notification_preferences';

        $existing = $this->get_user_pref($user_id, $event, $channel);
        $data = [
            'user_id'    => $user_id,
            'event_name' => $event,
            'channel'    => $channel,
            'is_enabled' => $enabled ? 1 : 0,
            'quiet_hours' => $quiet_hours,
        ];

        if ($existing) {
            $this->db->update($table, $data, ['id' => (int)$existing['id']]);
            return;
        }
        $this->db->insert($table, $data);
    }
}
