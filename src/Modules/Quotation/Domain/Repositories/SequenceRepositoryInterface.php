<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Quotation\Domain\Repositories;

interface SequenceRepositoryInterface
{
    public function increment(string $type, string $period): int; // atomic ++, trả về số mới
}
