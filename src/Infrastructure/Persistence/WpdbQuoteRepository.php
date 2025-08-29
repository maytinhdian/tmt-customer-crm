<?php

declare(strict_types=1);

namespace TMT\CRM\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Application\DTO\{QuoteDTO, QuoteItemDTO};
use TMT\CRM\Domain\Repositories\QuoteRepositoryInterface;

final class WpdbQuoteRepository implements QuoteRepositoryInterface
{
    public function __construct(private wpdb $db) {}

    private function t(string $base): string
    {
        return $this->db->prefix . $base;
    }

    public function next_code(string $yyyymm): string
    {
        $seq = $this->t('tmt_sequences');
        // atomic tăng số: dùng LAST_INSERT_ID trick
        $type = 'quote';
        $sql = "
            INSERT INTO {$seq} (`type`,`period`,`last_no`)
            VALUES (%s, %s, 1)
            ON DUPLICATE KEY UPDATE last_no = LAST_INSERT_ID(last_no + 1)
        ";
        $this->db->query($this->db->prepare($sql, $type, $yyyymm));
        $no = (int)$this->db->get_var('SELECT LAST_INSERT_ID()');
        return sprintf('QUO-%s-%04d', $yyyymm, $no);
    }

    public function save(QuoteDTO $dto): int
    {
        $table = $this->t('tmt_quotes');
        $now = current_time('mysql'); // WP time (mysql datetime)

        $data = [
            'code'           => $dto->code,
            'status'         => $dto->status,
            'customer_id'    => $dto->customer_id,
            'company_id'     => $dto->company_id,
            'owner_id'       => $dto->owner_id,
            'currency'       => $dto->currency,
            'expires_at'     => $dto->expires_at?->format('Y-m-d'),
            'note'           => $dto->note,
            'subtotal'       => $dto->subtotal,
            'discount_total' => $dto->discount_total,
            'tax_total'      => $dto->tax_total,
            'grand_total'    => $dto->grand_total,
            'created_at'     => $now,
            'updated_at'     => $now,
        ];
        $formats = ['%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%s'];

        $ok = $this->db->insert($table, $data, $formats);
        if ($ok === false) {
            throw new \RuntimeException('DB insert quote failed: ' . $this->db->last_error);
        }
        return (int)$this->db->insert_id;
    }

    public function replace_items(int $quote_id, array $items): void
    {
        $table = $this->t('tmt_quote_items');
        $this->db->delete($table, ['quote_id' => $quote_id], ['%d']);

        foreach ($items as $it) {
            if (!$it instanceof QuoteItemDTO) continue;
            $data = [
                'quote_id'   => $quote_id,
                'product_id' => $it->product_id,
                'sku'        => $it->sku,
                'name'       => $it->name,
                'qty'        => $it->qty,
                'unit_price' => $it->unit_price,
                'discount'   => $it->discount,
                'tax_rate'   => $it->tax_rate,
                'line_total' => $it->line_total,
            ];
            $formats = ['%d', '%d', '%s', '%s', '%f', '%f', '%f', '%f', '%f'];
            $ok = $this->db->insert($table, $data, $formats);
            if ($ok === false) {
                throw new \RuntimeException('DB insert quote item failed: ' . $this->db->last_error);
            }
        }
    }

    public function find_by_id(int $id): ?QuoteDTO
    {
        $qtable = $this->t('tmt_quotes');
        $itable = $this->t('tmt_quote_items');

        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$qtable} WHERE id=%d", $id),
            ARRAY_A
        );
        if (!$row) return null;

        $dto = new QuoteDTO();
        $dto->id            = (int)$row['id'];
        $dto->code          = (string)$row['code'];
        $dto->status        = (string)$row['status'];
        $dto->customer_id   = (int)$row['customer_id'];
        $dto->company_id    = $row['company_id'] !== null ? (int)$row['company_id'] : null;
        $dto->owner_id      = (int)$row['owner_id'];
        $dto->currency      = (string)$row['currency'];
        $dto->expires_at    = !empty($row['expires_at']) ? new \DateTimeImmutable($row['expires_at']) : null;
        $dto->note          = (string)$row['note'];
        $dto->subtotal      = (float)$row['subtotal'];
        $dto->discount_total = (float)$row['discount_total'];
        $dto->tax_total     = (float)$row['tax_total'];
        $dto->grand_total   = (float)$row['grand_total'];

        $items = $this->db->get_results(
            $this->db->prepare("SELECT * FROM {$itable} WHERE quote_id=%d ORDER BY id ASC", $id),
            ARRAY_A
        ) ?: [];

        $dto->items = array_map(function (array $r): QuoteItemDTO {
            $it = new QuoteItemDTO();
            $it->id         = (int)$r['id'];
            $it->product_id = $r['product_id'] !== null ? (int)$r['product_id'] : null;
            $it->sku        = (string)($r['sku'] ?? '');
            $it->name       = (string)$r['name'];
            $it->qty        = (float)$r['qty'];
            $it->unit_price = (float)$r['unit_price'];
            $it->discount   = (float)$r['discount'];
            $it->tax_rate   = (float)$r['tax_rate'];
            $it->line_total = (float)$r['line_total'];
            return $it;
        }, $items);

        return $dto;
    }

    public function update_status(int $id, string $status): void
    {
        $table = $this->t('tmt_quotes');
        $ok = $this->db->update(
            $table,
            ['status' => $status, 'updated_at' => current_time('mysql')],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );
        if ($ok === false) {
            throw new \RuntimeException('DB update status failed: ' . $this->db->last_error);
        }
    }
}
