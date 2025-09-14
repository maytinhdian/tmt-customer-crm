<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Quotation\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Modules\Quotation\Domain\Repositories\SequenceRepositoryInterface;

final class WpdbSequenceRepository implements SequenceRepositoryInterface
{
    public function __construct(private wpdb $db) {}
    private function t(): string
    {
        return $this->db->prefix . 'tmt_crm_sequences';
    }

    public function increment(string $type, string $period): int
    {
        $t = $this->t();
        $sql = "
         INSERT INTO {$t} (`type`,`period`,`last_no`)
         VALUES (%s,%s,1)
         ON DUPLICATE KEY UPDATE last_no = LAST_INSERT_ID(last_no + 1)
        ";
        $this->db->query($this->db->prepare($sql, $type, $period));
        return (int)$this->db->get_var('SELECT LAST_INSERT_ID()');
    }
}
