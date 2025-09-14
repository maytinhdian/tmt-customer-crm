<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Quotation\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Modules\Quotation\Application\DTO\{QuoteDTO, QuoteItemDTO};
use TMT\CRM\Modules\Quotation\Domain\Repositories\QuoteRepositoryInterface;

final class WpdbQuoteRepository implements QuoteRepositoryInterface
{
    public function __construct(private wpdb $db) {}
    private function tq(): string
    {
        return $this->db->prefix . 'tmt_crm_quotes';
    }
    private function ti(): string
    {
        return $this->db->prefix . 'tmt_crm_quote_items';
    }

    public function save(QuoteDTO $dto): int
    {
        $now = current_time('mysql'); // WP timezone
        $data = [
            'code'           => $dto->code,
            'status'         => $dto->status,
            'customer_id'    => $dto->customer_id,
            'company_id'     => $dto->company_id,
            'owner_id'       => $dto->owner_id,
            'currency'       => $dto->currency,
            'expires_at'     => $dto->expires_at ? $dto->expires_at->format('Y-m-d 00:00:00') : null,
            'note'           => $dto->note,
            'subtotal'       => $dto->subtotal,
            'discount_total' => $dto->discount_total,
            'tax_total'      => $dto->tax_total,
            'grand_total'    => $dto->grand_total,
            'created_at'     => $now,
            'updated_at'     => $now,
        ];
        $format = ['%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%s'];

        $this->db->insert($this->tq(), $data, $format);
        return (int)$this->db->insert_id;
    }

    public function replace_items(int $quote_id, array $items): void
    {
        $this->db->query('DELETE FROM ' . $this->ti() . ' WHERE quote_id=' . (int)$quote_id);
        foreach ($items as $it) {
            if (!$it instanceof QuoteItemDTO) continue;
            $this->db->insert($this->ti(), [
                'quote_id'   => $quote_id,
                'product_id' => $it->product_id,
                'sku'        => $it->sku,
                'name'       => $it->name,
                'qty'        => $it->qty,
                'unit_price' => $it->unit_price,
                'discount'   => $it->discount,
                'tax_rate'   => $it->tax_rate,
                'line_total' => $it->line_total,
            ], ['%d', '%d', '%s', '%s', '%f', '%f', '%f', '%f', '%f']);
        }
    }

    public function find_by_id(int $id): ?QuoteDTO
    {
        $q = $this->db->get_row(
            $this->db->prepare('SELECT * FROM ' . $this->tq() . ' WHERE id=%d', $id),
            ARRAY_A
        );
        if (!$q) return null;

        $dto = new QuoteDTO();
        $dto->id = (int)$q['id'];
        $dto->code = (string)$q['code'];
        $dto->status = (string)$q['status'];
        $dto->customer_id = (int)$q['customer_id'];
        $dto->company_id  = $q['company_id'] !== null ? (int)$q['company_id'] : null;
        $dto->owner_id = (int)$q['owner_id'];
        $dto->currency = (string)$q['currency'];
        $dto->expires_at = !empty($q['expires_at']) ? new \DateTimeImmutable($q['expires_at']) : null;
        $dto->note = (string)$q['note'];
        $dto->subtotal = (float)$q['subtotal'];
        $dto->discount_total = (float)$q['discount_total'];
        $dto->tax_total = (float)$q['tax_total'];
        $dto->grand_total = (float)$q['grand_total'];
        $dto->created_at = !empty($q['created_at']) ? new \DateTimeImmutable($q['created_at']) : null;
        $dto->updated_at = !empty($q['updated_at']) ? new \DateTimeImmutable($q['updated_at']) : null;

        $rows = $this->db->get_results(
            $this->db->prepare('SELECT * FROM ' . $this->ti() . ' WHERE quote_id=%d ORDER BY id ASC', $dto->id),
            ARRAY_A
        );
        $dto->items = [];
        foreach ($rows as $r) {
            $it = new QuoteItemDTO();
            $it->id         = (int)$r['id'];
            $it->quote_id   = (int)$r['quote_id'];
            $it->product_id = $r['product_id'] !== null ? (int)$r['product_id'] : null;
            $it->sku        = (string)$r['sku'];
            $it->name       = (string)$r['name'];
            $it->qty        = (float)$r['qty'];
            $it->unit_price = (float)$r['unit_price'];
            $it->discount   = (float)$r['discount'];
            $it->tax_rate   = (float)$r['tax_rate'];
            $it->line_total = (float)$r['line_total'];
            $dto->items[] = $it;
        }
        return $dto;
    }

    public function update_status(int $id, string $status): void
    {
        $this->db->update($this->tq(), [
            'status'     => $status,
            'updated_at' => current_time('mysql'),
        ], ['id' => $id], ['%s', '%s'], ['%d']);
    }
}
