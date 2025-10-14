<?php

namespace TMT\CRM\Modules\Customer\Domain\Entities;

use TMT\CRM\Shared\Traits\AsArrayTrait;

final class Customer
{
    use AsArrayTrait;
    
    public function __construct(
        public ?int $id,
        public string $full_name,
        public string $phone,
        public string $email,
        public ?string $address = null,
        public ?string $note = null,
    ) {}
}
