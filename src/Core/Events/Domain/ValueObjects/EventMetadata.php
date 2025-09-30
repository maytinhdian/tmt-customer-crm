<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Events\Domain\ValueObjects;

final class EventMetadata
{
    public function __construct(
        public readonly string $event_id,
        public readonly \DateTimeImmutable $occurred_at,
        public readonly ?int $actor_id = null,
        public readonly ?string $correlation_id = null,
        public readonly ?string $tenant = null,
    ) {}
}
