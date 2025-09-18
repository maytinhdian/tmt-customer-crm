<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Numbering\Domain\DTO;

/**
 * Data Transfer Object cho rule đánh số
 */
final class NumberingRuleDTO
{
    public function __construct(
        public string $entity_type,
        public string $prefix = '',
        public string $suffix = '',
        public int $padding = 4,
        public string $reset = 'never', // never|yearly|monthly
        public int $last_number = 0,
        public int $year_key = 0,   // dùng cho reset theo năm
        public int $month_key = 0   // dùng cho reset theo tháng
    ) {}
}
