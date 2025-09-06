<?php
declare(strict_types=1);

namespace TMT\CRM\Shared\Support;

final class Redirect
{
    public static function to(string $url): void
    {
        wp_safe_redirect($url);
        exit;
    }
}
