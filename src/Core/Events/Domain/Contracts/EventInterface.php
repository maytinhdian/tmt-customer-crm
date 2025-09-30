<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Events\Domain\Contracts;

use TMT\CRM\Core\Events\Domain\ValueObjects\EventMetadata;

interface EventInterface
{
    public function name(): string;
    public function payload(): object;
    public function metadata(): EventMetadata;
}
