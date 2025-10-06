<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Events\Domain\Events;

use TMT\CRM\Core\Events\Domain\Contracts\EventInterface;
use TMT\CRM\Core\Events\Domain\ValueObjects\EventMetadata;

final class DefaultEvent implements EventInterface
{
    public function __construct(
        private string $name,
        private object $payload,
        private EventMetadata $metadata
    ) {}

    public function name(): string { return $this->name; }
    public function payload(): object { return $this->payload; }
    public function metadata(): EventMetadata { return $this->metadata; }
}
