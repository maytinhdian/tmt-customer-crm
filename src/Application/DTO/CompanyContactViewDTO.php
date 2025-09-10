<?php
// src/Application/DTO/CompanyContactViewDTO.php
namespace TMT\CRM\Application\DTO;

/**
 * Dùng làm dữ liệu đầu vào cho CompanyContactListTable
 */
final class CompanyContactViewDTO
{
    public int $id;
    public int $company_id;
    public ?int $customer_id;

    // Từ customers (ưu tiên hiển thị)
    public string  $full_name;     // fallback: contact_name → #customer_id
    public ?string $phone;
    public ?string $email;

    // Từ company_contacts
    public ?string $role;
    public ?string $position;
    public ?string $start_date;
    public ?string $end_date;
    public ?int    $is_primary;

    // Người phụ trách (owner) – có thể lấy từ company_contacts hoặc bảng company tùy schema
    public ?int    $owner_id;
    public ?string $owner_name;
    public ?string $owner_phone; // nếu có custom user meta
    public ?string $owner_email;

    public function __construct(
        int $id,
        int $company_id,
        ?int $customer_id,
        string $full_name,
        ?string $phone,
        ?string $email,
        ?string $role,
        ?string $position,
        ?string $start_date,
        ?string $end_date,
        ?int $is_primary,
        ?int $owner_id,
        ?string $owner_name,
        ?string $owner_phone,
        ?string $owner_email
    ) {
        $this->id          = $id;
        $this->company_id  = $company_id;
        $this->customer_id = $customer_id;

        $this->full_name   = $full_name;
        $this->phone       = $phone;
        $this->email       = $email;

        $this->role        = $role;
        $this->position    = $position;
        $this->start_date  = $start_date;
        $this->end_date    = $end_date;
        $this->is_primary  = $is_primary;

        $this->owner_id    = $owner_id;
        $this->owner_name  = $owner_name;
        $this->owner_phone = $owner_phone;
        $this->owner_email = $owner_email;
    }
}
