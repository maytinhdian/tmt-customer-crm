<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Company\Application\DTO;

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
    public ?int $owner_id;         // ⬅️ mới
    public ?string $representer;   // ⬅️ mới
    public ?string $created_at;
    public ?string $updated_at;

    // 🔽 bổ sung các field soft-delete
    public ?string $status = null;          // 'active' | 'inactive'
    public ?string $deleted_at = null;
    public ?int $deleted_by = null;
    public ?string $deleted_by_name = null; // join sang bảng users khi query
    public ?string $delete_reason = null;
    public ?string $restored_at = null;
    public ?int $restored_by = null;

    public function __construct(
        ?int $id,
        string $name,
        string $tax_code,
        string $address,
        ?string $phone = null,
        ?string $email = null,
        ?string $website = null,
        ?string $note = null,
        ?int $owner_id = null,
        ?string $representer = null,
        ?string $created_at = null,
        ?string $updated_at = null
    ) {
        $this->id          = $id;
        $this->name        = trim($name);
        $this->tax_code    = trim($tax_code);
        $this->address     = trim($address);
        $this->phone       = $this->nn($phone);
        $this->email       = $this->nn($email);
        $this->website     = $this->nn($website);
        $this->note        = $this->nn($note);
        $this->owner_id    = $owner_id;
        $this->representer = $representer;
        $this->created_at  = $created_at;
        $this->updated_at  = $updated_at;
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
        $dto = new self(
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
            owner_id: isset($data['owner_id']) ? (int)$data['owner_id'] : null,
            representer: isset($data['representer']) ? (string)$data['representer'] : null,
            created_at: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updated_at: isset($data['updated_at']) ? (string)$data['updated_at'] : null
        );

        // map thêm các field soft-delete
        $dto->status          = $data['status']          ?? null;
        $dto->deleted_at      = $data['deleted_at']      ?? null;
        $dto->deleted_by      = isset($data['deleted_by']) ? (int)$data['deleted_by'] : null;
        $dto->deleted_by_name = $data['deleted_by_name'] ?? null;
        $dto->delete_reason   = $data['delete_reason']   ?? null;
        $dto->restored_at     = $data['restored_at']     ?? null;
        $dto->restored_by     = isset($data['restored_by']) ? (int)$data['restored_by'] : null;

        return $dto;
    }

    private function nn(?string $v): ?string
    {
        $t = trim((string)$v);
        return $t !== '' ? $t : null;
    }
}
