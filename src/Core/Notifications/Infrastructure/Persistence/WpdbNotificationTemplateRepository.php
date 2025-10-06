<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Infrastructure\Persistence;

use TMT\CRM\Domain\Repositories\NotificationTemplateRepositoryInterface;
use wpdb;

final class WpdbNotificationTemplateRepository implements NotificationTemplateRepositoryInterface
{
    public function __construct(private wpdb $db) {}

    public function find_by_code(string $code): ?array
    {
        $table = $this->db->prefix . 'tmt_crm_notification_templates';
        $sql   = $this->db->prepare("SELECT * FROM {$table} WHERE code = %s LIMIT 1", $code);
        $row   = $this->db->get_row($sql, ARRAY_A);

        return $row ?: null;
    }

    public function upsert(array $tpl): int
    {
        $table = $this->db->prefix . 'tmt_crm_notification_templates';
        $now   = current_time('mysql');

        $existing = $this->find_by_code((string) $tpl['code']);
        $data = [
            'code'        => (string) $tpl['code'],
            'channel'     => (string) $tpl['channel'],
            'subject_tpl' => $tpl['subject_tpl'] ?? null,
            'body_tpl'    => $tpl['body_tpl'] ?? null,
            'is_active'   => (int) ($tpl['is_active'] ?? 1),
            'updated_at'  => $now,
        ];

        if ($existing) {
            $this->db->update($table, $data, ['id' => (int)$existing['id']]);
            return (int)$existing['id'];
        }

        $this->db->insert($table, $data);
        return (int)$this->db->insert_id;
    }

    public function list(array $filters = [], int $page = 1, int $per_page = 20): array
    {
        $table  = $this->db->prefix . 'tmt_crm_notification_templates';
        $where  = [];
        $params = [];

        if (!empty($filters['channel'])) {
            $where[]  = 'channel = %s';
            $params[] = (string)$filters['channel'];
        }
        if (isset($filters['is_active'])) {
            $where[]  = 'is_active = %d';
            $params[] = (int)$filters['is_active'];
        }

        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset    = max(0, ($page - 1) * $per_page);

        // Lưu ý: prepare với biến động số tham số
        $base = "SELECT * FROM {$table} {$where_sql} ORDER BY updated_at DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $sql = $this->db->prepare($base, ...$params);
        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }
}
