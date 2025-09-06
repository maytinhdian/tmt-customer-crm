<?php

declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

interface TransactionManagerInterface
{
    public function begin(): void;
    public function commit(): void;
    public function rollback(): void;
}
