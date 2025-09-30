<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Numbering\Application;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Numbering\Domain\Services\NumberingService;

/**
 * Facade để các module khác gọi sinh mã: NumberingFacade::next_number('company', [...context])
 */
final class NumberingFacade
{
    /**
     * Sinh mã tiếp theo cho entity.
     * @param string $entity_type  Ví dụ: 'company', 'customer', 'contact', 'quote'...
     * @param array  $context      Tham số bổ sung: ['year' => 2025, 'month' => 9, ...]
     */
    public static function next_number(string $entity_type, array $context = []): string
    {
        /** @var NumberingService $svc */
        $svc = Container::get(NumberingService::class);
        return $svc->next_number($entity_type, $context);
    }
}
