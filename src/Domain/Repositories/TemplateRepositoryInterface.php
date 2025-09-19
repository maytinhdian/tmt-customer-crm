<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Core\Notifications\Domain\DTO\TemplateDTO;

interface TemplateRepositoryInterface
{
    public function find_by_key(string $key): ?TemplateDTO;
    /** @return TemplateDTO[] */
    public function find_all_by_channel(string $channel): array;
}
