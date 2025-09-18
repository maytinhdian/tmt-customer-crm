<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Core\Numbering\Domain\DTO\NumberingRuleDTO;

interface NumberingRepositoryInterface
{
    /** Lấy rule theo entity_type (null nếu chưa có) */
    public function get_rule(string $entity_type): ?NumberingRuleDTO;

    /** Lưu (insert/update) rule */
    public function save_rule(NumberingRuleDTO $rule): void;

    /** Tăng last_number và trả về giá trị mới (đảm bảo atomic khi concurrent) */
    public function increment_and_get(string $entity_type, int $year_key = 0, int $month_key = 0): int;
}
