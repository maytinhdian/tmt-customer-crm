<?php

declare(strict_types=1);

namespace TMT\CRM\Shared\Support;

final class Nonce
{
    public static function field(string $action, int|string $ref_id): string
    {
        return wp_nonce_field($action . '_' . $ref_id, '_wpnonce', true, false);
    }

    public static function verify_or_die(string $action, int|string $ref_id): void
    {
        check_admin_referer($action . '_' . $ref_id);
    }
}
