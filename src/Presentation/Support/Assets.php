<?php

declare(strict_types=1);

namespace TMT\CRM\Shared\Support;

final class Assets
{
    public static function enqueue_admin_css(string $handle, string $path_rel): void
    {
        $url = plugins_url($path_rel, TMT_CRM_PLUGIN_FILE);
        wp_enqueue_style($handle, $url, [], TMT_CRM_VERSION);
    }

    public static function enqueue_admin_js(string $handle, string $path_rel, array $deps = ['jquery']): void
    {
        $url = plugins_url($path_rel, TMT_CRM_PLUGIN_FILE);
        wp_enqueue_script($handle, $url, $deps, TMT_CRM_VERSION, true);
    }
}
