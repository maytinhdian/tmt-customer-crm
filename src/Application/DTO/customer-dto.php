<?php
namespace TMT\CRM\Application\DTO;

final class Customer_DTO {
    public function __construct(
        public string $full_name,
        public string $phone,
        public string $email,
        public ?int $company_id = null,
        public ?string $address = null,
        public ?string $tags = null,
        public ?string $note = null,
    ) {}
}