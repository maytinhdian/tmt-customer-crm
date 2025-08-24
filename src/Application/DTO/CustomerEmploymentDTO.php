<?php

declare(strict_types=1);

namespace TMT\CRM\Application\DTO;

use TMT\CRM\Shared\Traits\AsArrayTrait;

/**
 * CustomerEmploymentDTO
 * - Lịch sử làm việc của 1 khách hàng (customer) tại các công ty theo thời gian.
 * - end_date = NULL nghĩa là đang làm.
 */
final class CustomerEmploymentDTO implements \JsonSerializable
{
    use AsArrayTrait;

    public ?int $id = null;

    public int $customer_id;
    public int $company_id;

    /** 'Y-m-d' */
    public string $start_date;
    /** 'Y-m-d' | NULL khi còn hiệu lực */
    public ?string $end_date = null;

    /** Đánh dấu mối quan hệ chính */
    public bool $is_primary = true;

    /** 'Y-m-d H:i:s' (do DB sinh) */
    public ?string $created_at = null;
    /** 'Y-m-d H:i:s' (do DB sinh) */
    public ?string $updated_at = null;

    /**
     * Factory: map array (DB/Request) → DTO
     * - Hỗ trợ fallback key cho ngày bắt đầu/kết thúc:
     *   start_date ← start | start_at
     *   end_date   ← end   | end_at
     * - is_primary: nhận 1/0, true/false, 'yes'/'no'
     */
    public static function from_array(array $data): self
    {
        $dto = new self();

        $dto->id          = isset($data['id']) ? (int)$data['id'] : null;
        $dto->customer_id = isset($data['customer_id'])
            ? (int)$data['customer_id']
            : (isset($data['customer']) ? (int)$data['customer'] : 0);

        $dto->company_id  = isset($data['company_id'])
            ? (int)$data['company_id']
            : (isset($data['company']) ? (int)$data['company'] : 0);

        // Ngày bắt đầu: bắt buộc, nếu thiếu thì mặc định hôm nay
        $dto->start_date  = (string)($data['start_date']
            ?? $data['start']
            ?? $data['start_at']
            ?? date('Y-m-d'));

        // Ngày kết thúc: NULL nếu rỗng
        if (array_key_exists('end_date', $data)) {
            $dto->end_date = ($data['end_date'] === '' || $data['end_date'] === null) ? null : (string)$data['end_date'];
        } elseif (array_key_exists('end', $data)) {
            $dto->end_date = ($data['end'] === '' || $data['end'] === null) ? null : (string)$data['end'];
        } elseif (array_key_exists('end_at', $data)) {
            $dto->end_date = ($data['end_at'] === '' || $data['end_at'] === null) ? null : (string)$data['end_at'];
        } else {
            $dto->end_date = null;
        }

        // is_primary: parse bool linh hoạt
        $raw = $data['is_primary'] ?? $data['primary'] ?? 1;
        $bool = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $dto->is_primary = $bool !== null ? $bool : ((string)$raw === '1' || (int)$raw === 1);

        $dto->created_at = isset($data['created_at']) ? (string)$data['created_at'] : null;
        $dto->updated_at = isset($data['updated_at']) ? (string)$data['updated_at'] : null;

        return $dto;
    }

    /**
     * Hỗ trợ json_encode($dto)
     */
    public function jsonSerialize(): array
    {
        return $this->to_array();
    }
}
