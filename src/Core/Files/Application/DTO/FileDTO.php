<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Files\Application\DTO;

use TMT\CRM\Shared\Traits\AsArrayTrait;

final class FileDTO
{
    use AsArrayTrait;

    public ?int $id = null;
    public string $entity_type;   // 'company' | 'customer'
    public int $entity_id;
    public int $attachment_id;    // WP attachment ID
    public int $uploaded_by;
    public ?string $uploaded_at = null;
}
