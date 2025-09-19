<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Domain\DTO;

final class NotificationDTO
{
    public int $id = 0;
    public string $event_key = '';
    public string $entity_type = '';
    public int $entity_id = 0;
    public string $template_key = '';
    public string $summary = '';
    public string $created_at = '';
    public int $created_by = 0;
}
