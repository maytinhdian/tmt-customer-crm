<?php

declare(strict_types=1);

namespace TMT\CRM\Shared\Support;

final class Datex
{
    public static function today(): string
    {
        return (new \DateTime('today'))->format('Y-m-d');
    }

    public static function is_active(?string $end_date, ?string $ref = null): bool
    {
        if (!$end_date) return true;
        $ref = $ref ?: self::today();
        return $end_date >= $ref;
    }

    public static function format_period(?string $start, ?string $end): string
    {
        return ($start ?: '—') . ' → ' . ($end ?: __('hiện tại', 'tmt-crm'));
    }
}
