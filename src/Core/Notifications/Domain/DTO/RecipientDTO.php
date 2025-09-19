<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Domain\DTO;

final class RecipientDTO
{
    public string $recipient_type = 'user';
    public string $recipient_value = '';
}
