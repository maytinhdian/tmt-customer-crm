<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Customer\Application\DTO;

use TMT\CRM\Shared\Traits\AsArrayTrait;

final class CustomerDTO implements \JsonSerializable
{
     use AsArrayTrait;
     
    /** Khóa chính */
    public ?int $id = null;

    /** Tên khách hàng (bắt buộc về mặt nghiệp vụ) */
    public string $name = '';

    public ?string $email = null;
    public ?string $phone = null;
    public ?string $address = null;
    public ?string $note = null;

    /** User phụ trách (WP user id) */
    public ?int $owner_id = null;

    public ?string $created_at = null;
    public ?string $updated_at = null;

    /**
     * Gợi ý: Có thể truyền rỗng và set sau, tránh lỗi uninitialized.
     */
    public function __construct(
        ?int $id = null,
        string $name = '',
        ?string $email = null,
        ?string $phone = null,
        ?string $address = null,
        ?string $note = null,
        ?int $owner_id = null,
        ?string $created_at = null,
        ?string $updated_at = null
    ) {
        $this->id         = $id;
        $this->name       = $name;
        $this->email      = $email;
        $this->phone      = $phone;
        $this->address    = $address;
        $this->note       = $note;      
        $this->owner_id   = $owner_id;
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
     * - Hỗ trợ key 'company_name' (fallback cho 'company')
     */
    public static function from_array(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            name: (string) ($data['name'] ?? ''),
            email: isset($data['email']) ? (string) $data['email'] : null,
            phone: isset($data['phone']) ? (string) $data['phone'] : null,         
            address: isset($data['address']) ? (string) $data['address'] : null,
            note: isset($data['note']) ? (string) $data['note'] : null,
            owner_id: isset($data['owner_id']) ? (int) $data['owner_id'] : null,
            created_at: isset($data['created_at']) ? (string) $data['created_at'] : null,
            updated_at: isset($data['updated_at']) ? (string) $data['updated_at'] : null
        );
    }

    /**
     * (Tuỳ chọn) Helper nhẹ để đảm bảo name luôn có giá trị hợp lệ trước khi save.
     * Tránh validate mạnh ở DTO nếu đã validate tại Service.
     */
    public function ensure_min_requirements(): void
    {
        $this->name = trim($this->name);
    }
}
