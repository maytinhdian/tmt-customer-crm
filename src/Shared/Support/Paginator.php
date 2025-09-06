<?php

declare(strict_types=1);

namespace TMT\CRM\Shared\Support;

final class Paginator
{
    public static function clamp_page(int $page): int
    {
        return max(1, $page);
    }

    public static function total_pages(int $total_items, int $per_page): int
    {
        return (int) max(1, ceil($total_items / max(1, $per_page)));
    }
}
