<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\Core\Records\Application\DTO;

final class AuditLogDTO
{
    public int $id;
    public string $entity;
    public int $entity_id;
    public string $action; // CREATE|UPDATE|SOFT_DELETE|RESTORE|PURGE
    public int $actor_id;
    public ?string $reason = null;
    public ?array $diff = null;
    public ?string $ip = null;
    public ?string $user_agent = null;
    public \DateTimeImmutable $created_at;
    public ?int $archive_id = null;
}
