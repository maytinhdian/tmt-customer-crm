<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Quotation\Application\Services;

use TMT\CRM\Modules\Quotation\Application\DTO\{QuoteDTO, QuoteItemDTO};
use TMT\CRM\Modules\Quotation\Domain\Repositories\QuoteRepositoryInterface;

final class QuoteService
{
    public function __construct(
        private QuoteRepositoryInterface $quotes,
        private NumberingService $numbering
    ) {}

    public function create_draft(QuoteDTO $dto): QuoteDTO
    {
        $dto->code = $this->numbering->next_code('quote');
        $dto->status = 'draft';
        $this->recalc_totals($dto);
        $dto->id = $this->quotes->save($dto);
        $this->quotes->replace_items($dto->id, $dto->items);
        return $dto;
    }

    public function recalc_totals(QuoteDTO $dto): void
    {
        $sub = 0;
        $dis = 0;
        $tax = 0;
        foreach ($dto->items as $it) {
            if (!$it instanceof QuoteItemDTO) continue;
            $line_sub = $it->qty * $it->unit_price;
            $line_tax = max(0, $line_sub - $it->discount) * ($it->tax_rate / 100);
            $it->line_total = $line_sub - $it->discount + $line_tax;
            $sub += $line_sub;
            $dis += $it->discount;
            $tax += $line_tax;
        }
        $dto->subtotal = $sub;
        $dto->discount_total = $dis;
        $dto->tax_total = $tax;
        $dto->grand_total = $sub - $dis + $tax;
    }
}
