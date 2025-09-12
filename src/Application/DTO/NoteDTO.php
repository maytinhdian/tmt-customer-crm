<?php

declare(strict_types=1);

namespace TMT\CRM\Application\DTO;

use TMT\CRM\Shared\Traits\AsArrayTrait;

final class NoteDTO
{
    use AsArrayTrait;

    public ?int $id = null;
    public string $entity_type;   // 'company' | 'customer'
    public int $entity_id;
    public string $content;
    public int $created_by;
    public ?string $created_at = null;
}
