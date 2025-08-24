<?php

declare(strict_types=1);

namespace TMT\CRM\Application\DTO;

use TMT\CRM\Shared\Traits\AsArrayTrait;

/**
 * CompanyContactRoleDTO
 * - Gán vai trò liên hệ (kế toán/thu mua/xuất HĐ/...) cho 1 customer tại 1 company theo thời gian.
 * - end_date = NULL nghĩa là vai trò đang đảm nhiệm.
 */
final class CompanyContactRoleDTO implements \JsonSerializable
{
    use AsArrayTrait;

    public ?int $id = null;

    public int $company_id;
    public int $customer_id;

    /** Ví dụ: 'accounting' | 'purchasing' | 'invoice' | ... */
    public string $role;

    /** 'Y-m-d' */
    public string $start_date;
    /** 'Y-m-d' | NULL khi còn hiệu lực */
    public ?string $end_date = null;

    /** 'Y-m-d H:i:s' (do DB sinh) */
    public ?string $created_at = null;
    /** 'Y-m-d H:i:s' (do DB sinh) */
    public ?string $updated_at = null;

    /**
     * Hỗ trợ json_encode($dto)
     */
    public function jsonSerialize(): array
    {
        return $this->to_array();
    }

    /**
     * Factory: map array (DB/Request) → DTO
     * - Hỗ trợ key 'contact_role' | 'company_role' (fallback cho 'role')
     * - start_date ← start | start_at ; end_date ← end | end_at
     */
    public static function from_array(array $data): self
    {
        $dto = new self();

        $dto->id          = isset($data['id']) ? (int)$data['id'] : null;
        $dto->company_id  = isset($data['company_id'])
            ? (int)$data['company_id']
            : (isset($data['company']) ? (int)$data['company'] : 0);

        $dto->customer_id = isset($data['customer_id'])
            ? (int)$data['customer_id']
            : (isset($data['customer']) ? (int)$data['customer'] : 0);

        // role: nhận 'role' | 'contact_role' | 'company_role'
        $dto->role = (string)($data['role']
            ?? $data['contact_role']
            ?? $data['company_role']
            ?? '');

        // start_date: mặc định hôm nay nếu thiếu
        $dto->start_date = (string)($data['start_date']
            ?? $data['start']
            ?? $data['start_at']
            ?? date('Y-m-d'));

        // end_date: NULL nếu rỗng
        if (array_key_exists('end_date', $data)) {
            $dto->end_date = ($data['end_date'] === '' || $data['end_date'] === null) ? null : (string)$data['end_date'];
        } elseif (array_key_exists('end', $data)) {
            $dto->end_date = ($data['end'] === '' || $data['end'] === null) ? null : (string)$data['end'];
        } elseif (array_key_exists('end_at', $data)) {
            $dto->end_date = ($data['end_at'] === '' || $data['end_at'] === null) ? null : (string)$data['end_at'];
        } else {
            $dto->end_date = null;
        }

        $dto->created_at = isset($data['created_at']) ? (string)$data['created_at'] : null;
        $dto->updated_at = isset($data['updated_at']) ? (string)$data['updated_at'] : null;

        return $dto;
    }
}
