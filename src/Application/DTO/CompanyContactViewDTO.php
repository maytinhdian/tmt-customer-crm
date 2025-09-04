<?php

declare(strict_types=1);

namespace TMT\CRM\Application\DTO;

use TMT\CRM\Shared\Traits\AsArrayTrait;

final class CompanyContactViewDTO implements \JsonSerializable
{
    use AsArrayTrait;

    public int $id;
    public int $company_id;
    public int $customer_id;
    public string $role;
    public ?string $title;
    public bool $is_primary;
    public ?string $start_date;
    public ?string $end_date;

    // —— Trường “bên ngoài” lấy từ bảng khác —— //
    public ?string $customer_full_name;   // từ bảng customer
    public ?string $customer_phone;       // từ bảng customer
    public ?string $customer_email;       // từ bảng customer

    public ?int $owner_id;                // từ bảng companies
    public ?string $owner_name;           // từ wp_users

    public function __construct(
        int $id,
        int $company_id,
        int $customer_id,
        string $role = '',
        ?string $title = null,
        bool $is_primary = false,
        ?string $start_date = null,
        ?string $end_date = null,
        ?string $customer_full_name = null,
        ?string $customer_phone = null,
        ?string $customer_email = null,
        ?int $owner_id = null,
        ?string $owner_name = null
    ) {
        $this->id = $id;
        $this->company_id = $company_id;
        $this->customer_id = $customer_id;
        $this->role = $role;
        $this->title = $title;
        $this->is_primary = $is_primary;
        $this->start_date = $start_date;
        $this->end_date = $end_date;

        $this->customer_full_name = $customer_full_name;
        $this->customer_phone = $customer_phone;
        $this->customer_email = $customer_email;

        $this->owner_id = $owner_id;
        $this->owner_name = $owner_name;
    }
    /**
     * Hỗ trợ json_encode($dto)
     */
    public function jsonSerialize(): array
    {
        return $this->to_array();
    }
}
