<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\Core\Records\Application\Services;

use TMT\CRM\Modules\Core\Records\Application\DTO\ArchiveDTO;
use TMT\CRM\Modules\Core\Records\Application\DTO\AuditLogDTO;
use TMT\CRM\Modules\Core\Records\Domain\Repositories\ArchiveRepositoryInterface;
use TMT\CRM\Modules\Core\Records\Domain\Repositories\AuditLogRepositoryInterface;

final class HistoryService
{
    public function __construct(
        private AuditLogRepositoryInterface $audit_repo,
        private ArchiveRepositoryInterface $archive_repo
    ) {}

    /**
     * Tạo snapshot + audit cho PURGE (gọi trước khi DELETE thật).
     * $snapshot/$relations/$attachments là mảng thuần (sẽ json_encode).
     */
    public function snapshot_and_log_purge(
        string $entity,
        int $entity_id,
        array $snapshot,
        ?array $relations,
        ?array $attachments,
        int $actor_id,
        ?string $reason = null,
        ?string $ip = null,
        ?string $ua = null
    ): void {
        $archive = new ArchiveDTO();
        $archive->entity          = $entity;
        $archive->entity_id       = $entity_id;
        $archive->snapshot        = $snapshot;
        $archive->relations       = $relations;
        $archive->attachments     = $attachments;
        $archive->checksum_sha256 = hash('sha256', json_encode([$snapshot, $relations, $attachments], JSON_UNESCAPED_UNICODE));
        $archive->purged_by       = $actor_id;
        $archive->purged_at       = new \DateTimeImmutable('now');
        $archive->purge_reason    = $reason;

        $archive_id = $this->archive_repo->store_snapshot($archive);

        $log = new AuditLogDTO();
        $log->entity      = $entity;
        $log->entity_id   = $entity_id;
        $log->action      = 'PURGE';
        $log->actor_id    = $actor_id;
        $log->reason      = $reason;
        $log->ip          = $ip;
        $log->user_agent  = $ua;
        $log->created_at  = new \DateTimeImmutable('now');
        $log->archive_id  = $archive_id;

        $this->audit_repo->record_event($log);
    }
}
