<?php

declare(strict_types=1);

namespace TMT\CRM\Application\DTO;

final class EmploymentHistoryDTO
{
    public ?int $id;
    public int $customer_id;
    public int $company_id;
    public ?string $title;        // Chức danh
    public ?string $start_date;   // 'YYYY-MM-DD'
    public ?string $end_date;     // 'YYYY-MM-DD'
    public ?string $note;
    public ?string $created_at;
    public ?string $updated_at;

    public function __construct(
        ?int $id,
        int $customer_id,
        int $company_id,
        ?string $title = null,
        ?string $start_date = null,
        ?string $end_date = null,
        ?string $note = null,
        ?string $created_at = null,
        ?string $updated_at = null
    ) {
        $this->id = $id;
        $this->customer_id = $customer_id;
        $this->company_id = $company_id;
        $this->title = self::nn($title);
        $this->start_date = self::nn($start_date);
        $this->end_date = self::nn($end_date);
        $this->note = self::nn($note);
        $this->created_at = self::nn($created_at);
        $this->updated_at = self::nn($updated_at);
    }

    public static function from_array(array $row): self
    {
        return new self(
            isset($row['id']) ? (int)$row['id'] : null,
            (int)$row['customer_id'],
            (int)$row['company_id'],
            $row['title'] ?? null,
            $row['start_date'] ?? null,
            $row['end_date'] ?? null,
            $row['note'] ?? null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }

    public function to_array(): array
    {
        return [
            'id'          => $this->id,
            'customer_id' => $this->customer_id,
            'company_id'  => $this->company_id,
            'title'       => $this->title,
            'start_date'  => $this->start_date,
            'end_date'    => $this->end_date,
            'note'        => $this->note,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }

    private static function nn($v): ?string
    {
        $t = trim((string)$v);
        return $t !== '' ? $t : null;
    }
}
