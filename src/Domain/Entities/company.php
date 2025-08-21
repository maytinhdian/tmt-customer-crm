<?php
namespace TMT\CRM\Domain\Entities;

class Company
{
    public function __construct(
        public ?int $id,
        public string $name,
        public ?string $taxCode,
        public ?string $phone,
        public ?string $email,
        public ?string $website,
        public ?string $address,
        public ?string $note,
        public string $createdAt,
        public string $updatedAt
    ) {}
}