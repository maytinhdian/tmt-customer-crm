<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Domain\DTO;

final class TemplateDTO
{
    public function __construct(
        public string $subject,
        public string $body
    ) {}
}
