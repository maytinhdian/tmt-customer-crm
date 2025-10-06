<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Infrastructure\Persistence;

use TMT\CRM\Domain\Repositories\NotificationLogRepositoryInterface;
use wpdb;

final class WpdbNotificationLogRepository implements NotificationLogRepositoryInterface
{
    public function __construct(private wpdb $db) {}

    public function create(array $data): int
    {
        $table = $this->db->prefix . 'tmt_crm_notification_logs';

        $insert = [
            'template_code'   => $data['template_code']   ?? null,
            'event_name'      => $data['event_name']      ?? '',
            'channel'         => $data['channel']         ?? '',
            'recipient'       => $data['recipient']       ?? '',
            'subject'         => $data['subject']         ?? null,
            'status'          => $data['status']          ?? 'success',
            'error'           => $data['error']           ?? null,
            'run_id'          => $data['run_id']          ?? null,
            'idempotency_key' => $data['idempotency_key'] ?? '',
            'meta'            => isset($data['meta']) ? wp_json_encode($data['meta']) : null,
            'created_at'      => $data['created_at']      ?? current_time('mysql'),
        ];

        $this->db->insert($table, $insert);
        return (int) $this->db->insert_id;
    }

    public function find_recent_by_idempotency(string $key, int $ttl_seconds): ?array
    {
        $table = $this->db->prefix . 'tmt_crm_notification_logs';
        $since = gmdate('Y-m-d H:i:s', time() - $ttl_seconds);

        $sql = $this->db->prepare(
            "SELECT * FROM {$table}
             WHERE idempotency_key = %s AND created_at >= %s
             ORDER BY id DESC LIMIT 1",
            $key,
            $since
        );
        $row = $this->db->get_row($sql, ARRAY_A);

        if (!$row) {
            return null;
        }
        if (!empty($row['meta'])) {
            $row['meta'] = json_decode((string)$row['meta'], true) ?: [];
        }
        return $row;
    }

    public function stats_daily(string $since_date): array
    {
        $table = $this->db->prefix . 'tmt_crm_notification_logs';
        $sql = $this->db->prepare(
            "SELECT DATE(created_at) AS day, channel, status, COUNT(*) AS cnt
             FROM {$table}
             WHERE created_at >= %s
             GROUP BY 1,2,3
             ORDER BY 1 ASC",
            $since_date
        );
        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }
}
