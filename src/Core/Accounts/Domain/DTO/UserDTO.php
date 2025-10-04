<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Accounts\Domain\DTO;

final class UserDTO
{
    public function __construct(
        public int $id,
        public string $display_name,
        public ?string $email = null,
        public ?string $phone = null
    ) {}
}
