<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Numbering\Infrastructure;

use wpdb;
use TMT\CRM\Core\Numbering\Domain\DTO\NumberingRuleDTO;
use TMT\CRM\Domain\Repositories\NumberingRepositoryInterface;

final class WpdbNumberingRepository implements NumberingRepositoryInterface
{
    private string $table;

    public function __construct(private wpdb $db)
    {
        $this->table = $this->db->prefix . 'crm_numbering_rules';
    }

    public function get_rule(string $entity_type): ?NumberingRuleDTO
    {
        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM `{$this->table}` WHERE entity_type = %s LIMIT 1", $entity_type),
            ARRAY_A
        );
        if (!$row) {
            return null;
        }
        return new NumberingRuleDTO(
            entity_type: (string)$row['entity_type'],
            prefix: (string)$row['prefix'],
            suffix: (string)$row['suffix'],
            padding: (int)$row['padding'],
            reset: (string)$row['reset'],
            last_number: (int)$row['last_number'],
            year_key: (int)$row['year_key'],
            month_key: (int)$row['month_key'],
        );
    }

    public function save_rule(NumberingRuleDTO $rule): void
    {
        $exists = $this->db->get_var(
            $this->db->prepare("SELECT COUNT(*) FROM `{$this->table}` WHERE entity_type = %s", $rule->entity_type)
        );
        $data = [
            'entity_type' => $rule->entity_type,
            'prefix'      => $rule->prefix,
            'suffix'      => $rule->suffix,
            'padding'     => $rule->padding,
            'reset'       => $rule->reset,
            'last_number' => $rule->last_number,
            'year_key'    => $rule->year_key,
            'month_key'   => $rule->month_key,
            'updated_at'  => current_time('mysql'),
        ];
        if ((int)$exists > 0) {
            $this->db->update($this->table, $data, ['entity_type' => $rule->entity_type]);
        } else {
            $this->db->insert($this->table, $data);
        }
    }

    public function increment_and_get(string $entity_type, int $year_key = 0, int $month_key = 0): int
    {
        // Đảm bảo có row tồn tại (INSERT IGNORE)
        $this->db->query(
            $this->db->prepare(
                "INSERT IGNORE INTO `{$this->table}` (entity_type, year_key, month_key, last_number, reset, padding, prefix, suffix, updated_at)
                 VALUES (%s, %d, %d, 0, 'never', 4, '', '', NOW())",
                $entity_type, $year_key, $month_key
            )
        );

        // Tăng +1 một cách atomic bằng UPDATE
        $this->db->query(
            $this->db->prepare(
                "UPDATE `{$this->table}`
                 SET last_number = last_number + 1, year_key = %d, month_key = %d, updated_at = NOW()
                 WHERE entity_type = %s",
                $year_key, $month_key, $entity_type
            )
        );

        $val = (int)$this->db->get_var(
            $this->db->prepare(
                "SELECT last_number FROM `{$this->table}` WHERE entity_type = %s",
                $entity_type
            )
        );
        return $val;
    }
}
