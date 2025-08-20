<?php
// src/Domain/Entities/company.php
namespace TMT\CRM\Domain\Entities;

final class Company
{
    public function __construct(
        public ?int $id,
        public string $name,
        public ?string $tax_code = null,
        public ?string $address = null,
        public ?string $contact_person = null,
        public ?string $phone = null,
        public ?string $email = null,
    ) {}
}
