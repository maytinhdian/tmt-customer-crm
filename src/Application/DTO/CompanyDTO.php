<?php

declare(strict_types=1);

namespace TMT\CRM\Application\DTO;

use TMT\CRM\Shared\Traits\AsArrayTrait;

final class CompanyDTO implements \JsonSerializable
{
    use AsArrayTrait;
    public ?int $id;
    public string $name;           // bắt buộc
    public string $tax_code;       // bắt buộc
    public string $address;        // bắt buộc
    public ?string $phone;
    public ?string $email;
    public ?string $website;
    public ?string $note;
    public ?string $created_at;
    public ?string $updated_at;

    public function __construct(
        ?int $id,
        string $name,
        string $tax_code,
        string $address,
        ?string $phone = null,
        ?string $email = null,
        ?string $website = null,
        ?string $note = null,
        ?string $created_at = null,
        ?string $updated_at = null
    ) {
        $this->id         = $id;
        $this->name       = trim($name);
        $this->tax_code   = trim($tax_code);
        $this->address    = trim($address);
        $this->phone      = $this->nn($phone);
        $this->email      = $this->nn($email);
        $this->website    = $this->nn($website);
        $this->note       = $this->nn($note);
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    /**
     * Hỗ trợ json_encode($dto)
     */
    public function jsonSerialize(): array
    {
        return $this->to_array();
    }

    /**
     * Factory: map array (DB/Request) → DTO
     * - Hỗ trợ key 'company_name' (fallback cho 'name')
     */
    public static function from_array(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            name: array_key_exists('name', $data)
                ? (string)$data['name']
                : (array_key_exists('company_name', $data) ? (string)$data['company_name'] : ''),
            tax_code: isset($data['tax_code']) ? (string)$data['tax_code'] : '',
            address: isset($data['address']) ? (string)$data['address'] : '',
            phone: isset($data['phone']) ? (string)$data['phone'] : null,
            email: isset($data['email']) ? (string)$data['email'] : null,
            website: isset($data['website']) ? (string)$data['website'] : null,
            note: isset($data['note']) ? (string)$data['note'] : null,
            created_at: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updated_at: isset($data['updated_at']) ? (string)$data['updated_at'] : null
        );
    }

    private function nn(?string $v): ?string
    {
        $t = trim((string)$v);
        return $t !== '' ? $t : null;
    }
}
