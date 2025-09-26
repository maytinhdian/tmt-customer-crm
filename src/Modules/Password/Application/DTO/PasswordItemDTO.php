<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Password\Application\DTO;

final class PasswordItemDTO
{
    public function __construct(
        public ?int $id,
        public string $title,
        public ?string $username,
        public ?string $password,    // dạng plain khi tạo/sửa (không lưu DB)
        public ?string $url,
        public ?string $notes,
        public int $owner_id,
        public ?int $company_id,
        public ?int $customer_id,
        public string $subject,     // 'company' | 'customer'
        public ?string $category    // ví dụ: 'email','hosting','wifi',...
    ) {}

    public static function from_array(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            title: (string)($data['title'] ?? ''),
            username: isset($data['username']) ? (string)$data['username'] : null,
            password: isset($data['password']) ? (string)$data['password'] : null,
            url: isset($data['url']) ? (string)$data['url'] : null,
            notes: isset($data['notes']) ? (string)$data['notes'] : null,
            owner_id: isset($data['owner_id']) ? (int)$data['owner_id'] : get_current_user_id(),
            company_id: isset($data['company_id']) ? (int)$data['company_id'] : null,
            customer_id: isset($data['customer_id']) ? (int)$data['customer_id'] : null,
            subject: isset($data['subject']) ? (string)$data['subject'] : null,
            category: isset($data['category']) ? (string)$data['category'] : null,
        );
    }
}
