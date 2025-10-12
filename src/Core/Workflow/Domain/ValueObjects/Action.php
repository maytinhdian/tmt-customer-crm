<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Workflow\Domain\ValueObjects;

final class Action
{
    public function __construct(
        public readonly string $type, // notify|update_record|webhook|log
        public readonly array $payload = []
    ) {}
}
