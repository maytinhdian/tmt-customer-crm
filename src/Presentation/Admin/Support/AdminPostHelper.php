<?php
declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\Support;

final class AdminPostHelper
{
    public static function url(string $action, array $args, string $nonce_action): string
    {
        $url = add_query_arg(array_merge(['action' => $action], $args), admin_url('admin-post.php'));
        return wp_nonce_url($url, $nonce_action);
    }
}
