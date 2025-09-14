<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Quotation\Application\DTO;

final class QuoteItemDTO
{
    public ?int $id = null;
    public ?int $quote_id = null;
    public ?int $product_id = null;

    public string $sku = '';
    public string $name = '';

    public float $qty = 1.0;
    public float $unit_price = 0.0;
    public float $discount = 0.0; // theo tiền
    public float $tax_rate = 0.0; // %

    public float $line_total = 0.0;
}
