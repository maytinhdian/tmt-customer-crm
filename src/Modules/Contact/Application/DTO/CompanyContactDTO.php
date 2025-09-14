<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Contact\Application\DTO;

use TMT\CRM\Shared\Traits\AsArrayTrait;

final class CompanyContactDTO implements \JsonSerializable
{
    use AsArrayTrait;

    public ?int $id = null;
    public int $company_id;
    public int $customer_id;
    public string $role;          // 'accounting' | 'purchasing' | 'invoice_recipient' | 'decision_maker' | 'owner' | 'other'
    public ?string $title;        // Hiển thị chức danh nếu cần
    public ?int $is_primary = null;
    public ?string $start_date;   // 'YYYY-MM-DD'
    public ?string $end_date;     // 'YYYY-MM-DD'
    public ?string $note;
    public ?int $created_by;
    public ?string $created_at;
    public ?string $updated_at;

    public function __construct(
        ?int $id = null,
        ?int $company_id = null,
        ?int $customer_id = null,
        ?string $role = '',
        ?string $title = null,
        ?int $is_primary = null,
        ?string $start_date = null,
        ?string $end_date = null,
        ?string $note = null,
        ?int $created_by = null,
        ?string $created_at = null,
        ?string $updated_at = null
    ) {
        $this->id = $id;
        $this->company_id = $company_id;
        $this->customer_id = $customer_id;
        $this->role = $role;
        $this->title = self::nn($title);
        $this->is_primary = (($is_primary ?? null) === 1 ? 1 : null);
        $this->start_date = self::nn($start_date);
        $this->end_date = self::nn($end_date);
        $this->note = self::nn($note);
        $this->created_by = $created_by;
        $this->created_at = self::nn($created_at);
        $this->updated_at = self::nn($updated_at);
    }

    public static function from_array(array $data): self
    {
        return new self(
            isset($data['id']) ? (int)$data['id'] : null,
            (int)$data['company_id'],
            (int)$data['customer_id'],
            (string)$data['role'],
            $data['title'] ?? null,
            isset($data['is_primary']) && (int)$data['is_primary'] === '1' ? 1 : null,
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['note'] ?? null,
            (int)$data['created_by'],
            $data['created_at'] ?? null,
            $data['updated_at'] ?? null
        );
    }
    /**
     * Hỗ trợ json_encode($dto)
     */
    public function jsonSerialize(): array
    {
        return $this->to_array();
    }

    /***********************************************
     *  Helper                                     *
     *                                             *
     ***********************************************/
    private static function nn($v): ?string
    {
        $t = trim((string)$v);
        return $t !== '' ? $t : null;
    }
}
