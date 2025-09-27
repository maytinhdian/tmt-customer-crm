<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Domain\Entities;

/**
 * Entity: Credential (key/email/token…) – đối tượng gốc cần theo dõi.
 * Chỉ giữ dữ liệu. Nghiệp vụ xử lý đặt ở Application\Services.
 */
final class Credential
{
    public ?int $id;
    public string $number;
    public string $type;            // LICENSE_KEY | EMAIL_ACCOUNT | SAAS_ACCOUNT | API_TOKEN | WIFI_ACCOUNT | OTHER
    public string $label;

    public ?int $customer_id;
    public ?int $company_id;

    public string $status;          // active | disabled | expired | revoked | pending
    public ?\DateTimeInterface $expires_at;

    public ?int $seats_total;       // tổng ghế (nullable nếu không biết)
    public string $sharing_mode;    // none | seat_allocation | family_share
    public ?int $renewal_of_id;     // credential cũ nếu là gia hạn từ

    public ?int $owner_id;

    // Secrets (lưu ở DB dạng mã hóa; entity vẫn là "plaintext field holders" sau khi decrypt)
    public ?string $secret_primary;     // key/password
    public ?string $secret_secondary;   // app-password/backup-code
    public ?string $username;           // email/username (có thể plaintext để search)
    public ?string $extra_json;         // JSON cho cấu hình thêm (server, URL…)
    public ?string $secret_mask;        // hiển thị an toàn

    public \DateTimeInterface $created_at;
    public \DateTimeInterface $updated_at;

    // Soft delete
    public ?\DateTimeInterface $deleted_at;
    public ?int $deleted_by;
    public ?string $delete_reason;

    public function __construct(
        ?int $id,
        string $number,
        string $type,
        string $label,
        ?int $customer_id,
        ?int $company_id,
        string $status,
        ?\DateTimeInterface $expires_at,
        ?int $seats_total,
        string $sharing_mode,
        ?int $renewal_of_id,
        ?int $owner_id,
        ?string $secret_primary,
        ?string $secret_secondary,
        ?string $username,
        ?string $extra_json,
        ?string $secret_mask,
        \DateTimeInterface $created_at,
        \DateTimeInterface $updated_at,
        ?\DateTimeInterface $deleted_at,
        ?int $deleted_by,
        ?string $delete_reason
    ) {
        $this->id = $id;
        $this->number = $number;
        $this->type = $type;
        $this->label = $label;
        $this->customer_id = $customer_id;
        $this->company_id = $company_id;
        $this->status = $status;
        $this->expires_at = $expires_at;
        $this->seats_total = $seats_total;
        $this->sharing_mode = $sharing_mode;
        $this->renewal_of_id = $renewal_of_id;
        $this->owner_id = $owner_id;
        $this->secret_primary = $secret_primary;
        $this->secret_secondary = $secret_secondary;
        $this->username = $username;
        $this->extra_json = $extra_json;
        $this->secret_mask = $secret_mask;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
        $this->deleted_at = $deleted_at;
        $this->deleted_by = $deleted_by;
        $this->delete_reason = $delete_reason;
    }
}
