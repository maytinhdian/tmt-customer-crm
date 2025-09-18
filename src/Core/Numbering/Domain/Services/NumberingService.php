<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Numbering\Domain\Services;

use TMT\CRM\Domain\Repositories\NumberingRepositoryInterface;
use TMT\CRM\Core\Numbering\Domain\DTO\NumberingRuleDTO;

/**
 * Sinh mã theo rule
 */
final class NumberingService
{
    public function __construct(private NumberingRepositoryInterface $repo) {}

    /**
     * Sinh mã tiếp theo
     * - Tự xử lý reset theo tháng/năm
     * - Ghép prefix/padded/suffix
     * @param array $context Có thể chứa year, month để override (testing)
     */
    public function next_number(string $entity_type, array $context = []): string
    {
        $now = isset($context['now']) ? (int)$context['now'] : time();
        $year = (int)($context['year'] ?? (int)date('Y', $now));
        $month = (int)($context['month'] ?? (int)date('n', $now));

        $rule = $this->repo->get_rule($entity_type) ?? new NumberingRuleDTO(entity_type: $entity_type);

        // Xác định key reset
        $year_key = ($rule->reset === 'yearly' || $rule->reset === 'monthly') ? $year : 0;
        $month_key = ($rule->reset === 'monthly') ? $month : 0;

        // Nếu thay đổi key, reset bộ đếm
        if ($rule->year_key !== $year_key || $rule->month_key !== $month_key) {
            $rule->last_number = 0;
            $rule->year_key = $year_key;
            $rule->month_key = $month_key;
            $this->repo->save_rule($rule);
        }

        // Tăng và lấy số mới (atomic trong repo)
        $next = $this->repo->increment_and_get($entity_type, $year_key, $month_key);

        // Build mã
        $number = str_pad((string)$next, max(1, $rule->padding), '0', STR_PAD_LEFT);

        $replacements = [
            '{year}' => (string)$year,
            '{yy}'   => substr((string)$year, -2),
            '{month}'=> str_pad((string)$month, 2, '0', STR_PAD_LEFT),
        ];
        $prefix = strtr($rule->prefix, $replacements);
        $suffix = strtr($rule->suffix, $replacements);

        return $prefix . $number . $suffix;
    }
}
