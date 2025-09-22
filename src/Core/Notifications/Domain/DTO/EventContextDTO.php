<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Domain\DTO;

final class EventContextDTO
{
    public function __construct(
        public int $actor_id,
        public int $company_id
    ) {}
}
