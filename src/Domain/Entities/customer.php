<?php
namespace TMT\CRM\Domain\Entities;

final class Customer {
    public function __construct(
        public ?int $id,
        public string $full_name,
        public string $phone,
        public string $email,
        public ?string $address = null,
        public ?string $note = null,
    ) {}
}