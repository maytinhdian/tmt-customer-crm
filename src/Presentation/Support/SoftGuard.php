<?php

declare(strict_types=1);

namespace TMT\CRM\Shared\Support;

final class SortGuard
{
    /**
     * @param string $by     tham số yêu cầu (vd: $_GET['orderby'])
     * @param string[] $whitelist  danh sách cột cho phép
     * @param string $default      cột mặc định
     */
    public static function sanitize_order_by(?string $by, array $whitelist, string $default): string
    {
        $by = $by ? sanitize_key($by) : $default;
        return in_array($by, $whitelist, true) ? $by : $default;
    }

    public static function sanitize_dir(?string $dir, string $default = 'DESC'): string
    {
        $dir = $dir ? strtolower($dir) : $default;
        return $dir === 'asc' ? 'ASC' : 'DESC';
    }
}
