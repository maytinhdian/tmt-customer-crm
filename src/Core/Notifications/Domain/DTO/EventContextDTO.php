<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Domain\DTO;

/** Bối cảnh sự kiện domain truyền vào Dispatcher */
final class EventContextDTO
{
    /** DTO gốc từ module nghiệp vụ (CompanyDTO/QuoteDTO/...) */
    public array $payload = [];
    public int $actor_id = 0;
    public string $occurred_at = '';
}
