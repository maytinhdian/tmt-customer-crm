<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Password\Domain\Entities;

final class PasswordItem
{
    public function __construct(
        public ?int $id,
        public string $title,
        public ?string $username,
        public string $ciphertext,   // dữ liệu đã mã hoá (base64)
        public string $nonce,        // nonce (base64)
        public ?string $url,
        public ?string $notes,
        public int $owner_id,        // người tạo/owner
        public ?int $company_id,     // gắn công ty (nếu có)
        public ?int $customer_id,    // gắn khách lẻ (nếu có)
        public string $subject,
        public ?string $category,
        public string $created_at,
        public string $updated_at,
        public ?string $deleted_at = null
    ) {}
}
