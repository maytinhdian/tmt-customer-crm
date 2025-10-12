<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Workflow\Domain\ValueObjects;

final class Condition
{
    public function __construct(
        public readonly string $operator,  // equals, not_equals, contains, gt, lt...
        public readonly string $field,     // path: customer.status
        public readonly mixed $value
    ) {}
}
