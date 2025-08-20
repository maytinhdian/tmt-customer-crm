<?php
// src/Application/DTO/company-dto.php
namespace TMT\CRM\Application\DTO;


final class Company_DTO
{
    public function __construct(
        public string $name,
        public ?string $tax_code = null,
        public ?string $address = null,
        public ?string $contact_person = null,
        public ?string $phone = null,
        public ?string $email = null,
    ) {}
}
