<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\Core\Records\Application\DTO;

final class ArchiveDTO
{
    public int $id;
    public string $entity;
    public int $entity_id;
    public array $snapshot;
    public ?array $relations = null;
    public ?array $attachments = null;
    public string $checksum_sha256;
    public int $purged_by;
    public \DateTimeImmutable $purged_at;
    public ?string $purge_reason = null;
}
