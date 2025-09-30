<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Core\Log\Application\DTO\LogEntryDTO;

interface LogRepositoryInterface
{
    /** @return array{items: LogEntryDTO[], total:int} */
    public function search(
        ?string $level,
        ?string $channel,
        ?string $q,
        int $page,
        int $per_page
    ): array;

    /** @param array<string,mixed>|null $context */
    public function insert(
        string $level,
        string $message,
        ?array $context,
        ?string $channel = 'app',
        ?int $user_id = null,
        ?string $ip = null,
        ?string $module = null,
        ?string $request_id = null
    ): int;

    public function purge_older_than_days(int $days): int;
}
