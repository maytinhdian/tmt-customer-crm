<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Domain\DTO;

final class DeliveryDTO
{
    public int $id = 0;
    public int $notification_id = 0;
    public string $channel = ''; // notice|email|webhook|...
    public string $recipient_type = ''; // user|email|webhook
    public string $recipient_value = '';
    public string $status = 'queued'; // queued|sent|failed|read
    public int $attempts = 0;
    public ?string $last_error = null;
    public ?string $sent_at = null;
    public ?string $read_at = null; // đánh dấu đã đọc cho user
}
