<?php

declare(strict_types=1);

namespace TMT\CRM\Application\DTO;

final class QuoteDTO
{
    public ?int $id = null;
    public string $code = '';
    public string $status = 'draft';

    public int $customer_id;
    public ?int $company_id = null;
    public int $owner_id;

    public string $currency = 'VND';
    public ?\DateTimeImmutable $expires_at = null;

    /** @var QuoteItemDTO[] */
    public array $items = [];

    public float $subtotal = 0.0;
    public float $discount_total = 0.0;
    public float $tax_total = 0.0;
    public float $grand_total = 0.0;

    public string $note = '';

    public ?\DateTimeImmutable $created_at = null;
    public ?\DateTimeImmutable $updated_at = null;
}
