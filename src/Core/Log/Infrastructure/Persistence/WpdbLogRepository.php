<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Log\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Core\Log\Application\DTO\LogEntryDTO;
use TMT\CRM\Domain\Repositories\LogRepositoryInterface;

final class WpdbLogRepository implements LogRepositoryInterface
{
    public function __construct(private wpdb $db) {}

    public function insert(
        string $level,
        string $message,
        ?array $context,
        ?string $channel = 'app',
        ?int $user_id = null,
        ?string $ip = null,
        ?string $module = null,
        ?string $request_id = null
    ): int {
        $table = $this->db->prefix . 'tmt_crm_logs';
        $this->db->insert(
            $table,
            [
                'channel'    => $channel ?? 'app',
                'level'      => $level,
                'message'    => $message,
                'context'    => $context ? wp_json_encode($context) : null,
                'created_at' => current_time('mysql', true),
                'user_id'    => $user_id,
                'ip'         => $ip,
                'module'     => $module,
                'request_id' => $request_id,
            ],
            ['%s','%s','%s','%s','%s','%d','%s','%s','%s']
        );
        return (int) $this->db->insert_id;
    }

    public function search(?string $level, ?string $channel, ?string $q, int $page, int $per_page): array
    {
        $table = $this->db->prefix . 'tmt_crm_logs';
        $where = ['1=1']; $params = [];

        if ($level)   { $where[] = 'level=%s';   $params[] = $level; }
        if ($channel) { $where[] = 'channel=%s'; $params[] = $channel; }
        if ($q)       { $where[] = '(message LIKE %s)'; $params[] = '%' . $this->db->esc_like($q) . '%'; }

        $where_sql = 'WHERE ' . implode(' AND ', $where);
        $offset    = max(0, ($page - 1) * $per_page);

        $sql_items = $this->db->prepare(
            "SELECT * FROM {$table} {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d",
            ...array_merge($params, [$per_page, $offset])
        );
        $rows = $this->db->get_results($sql_items, ARRAY_A) ?: [];

        $items = array_map(function (array $r): LogEntryDTO {
            return new LogEntryDTO(
                id: (int)$r['id'],
                channel: (string)$r['channel'],
                level: (string)$r['level'],
                message: (string)$r['message'],
                context: $r['context'] ? json_decode((string)$r['context'], true) : null,
                created_at: (string)$r['created_at'],
                user_id: isset($r['user_id']) ? (int)$r['user_id'] : null,
                ip: $r['ip'] ?? null,
                module: $r['module'] ?? null,
                request_id: $r['request_id'] ?? null,
            );
        }, $rows);

        $sql_total = $this->db->prepare("SELECT COUNT(*) FROM {$table} {$where_sql}", ...$params);
        $total = (int) $this->db->get_var($sql_total);

        return ['items' => $items, 'total' => $total];
    }

    public function purge_older_than_days(int $days): int
    {
        $table = $this->db->prefix . 'tmt_crm_logs';
        $cut   = gmdate('Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS);
        $this->db->query(
            $this->db->prepare("DELETE FROM {$table} WHERE created_at < %s", $cut)
        );
        return (int) $this->db->rows_affected;
    }
}
