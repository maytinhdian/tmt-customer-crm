<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Workflow\Domain\ValueObjects;

final class Trigger
{
    public function __construct(
        public readonly string $type, // event|time|manual
        public readonly string $key,  // ví dụ: customer.created | cron expr
        public readonly array $options = []
    ) {}
}
