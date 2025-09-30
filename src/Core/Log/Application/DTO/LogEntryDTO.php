<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Log\Application\DTO;

final class LogEntryDTO
{
    public function __construct(
        public int $id,
        public string $channel,
        public string $level,
        public string $message,
        /** @var array<string,mixed>|null */
        public ?array $context,
        public string $created_at,
        public ?int $user_id = null,
        public ?string $ip = null,
        public ?string $module = null,
        public ?string $request_id = null,
    ) {}
}
