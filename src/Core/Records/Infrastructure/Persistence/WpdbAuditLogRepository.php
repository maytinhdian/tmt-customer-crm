<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Records\Infrastructure\Persistence;

use TMT\CRM\Core\Records\Application\DTO\AuditLogDTO;
use TMT\CRM\Domain\Repositories\AuditLogRepositoryInterface;

final class WpdbAuditLogRepository implements AuditLogRepositoryInterface
{
    public function record_event(AuditLogDTO $dto): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'crm_audit_logs';

        $data = [
            'entity'      => $dto->entity,
            'entity_id'   => $dto->entity_id,
            'action'      => $dto->action,
            'actor_id'    => $dto->actor_id,
            'reason'      => $dto->reason,
            'diff_json'   => $dto->diff ? wp_json_encode($dto->diff) : null,
            'ip_address'  => $dto->ip,
            'user_agent'  => $dto->user_agent,
            'created_at'  => $dto->created_at->format('Y-m-d H:i:s'),
            'archive_id'  => $dto->archive_id,
        ];
        $formats = ['%s','%d','%s','%d','%s','%s','%s','%s','%d'];

        $wpdb->insert($table, $data, $formats);
        return (int) $wpdb->insert_id;
    }
}
