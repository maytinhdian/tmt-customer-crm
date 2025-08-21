<?php

namespace TMT\CRM\Application\DTO;

class CompanyDTO
{
    public ?int $id = null;
    public string $name = "";
    public ?string $taxCode = null;
    public ?string $phone = null;
    public ?string $email = null;
    public ?string $website = null;
    public ?string $address = null;
    public ?string $note = null;

    public static function from_array(array $in): self
    {
        $dto = new self();
        $dto->id      = isset($in['id']) ? (int)$in['id'] : null;
        $dto->name    = trim((string)($in['name'] ?? ''));
        $dto->taxCode = self::nn($in['tax_code'] ?? null);
        $dto->phone   = self::nn($in['phone'] ?? null);
        $dto->email   = self::nn($in['email'] ?? null);
        $dto->website = self::nn($in['website'] ?? null);
        $dto->address = self::nn($in['address'] ?? null);
        $dto->note    = self::nn($in['note'] ?? null);
        return $dto;
    }

    private static function nn($v): ?string
    {
        $t = trim((string)$v);
        return $t !== '' ? $t : null;
    }
}
