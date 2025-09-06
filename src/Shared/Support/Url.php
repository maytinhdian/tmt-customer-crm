<?php
declare(strict_types=1);

namespace TMT\CRM\Shared\Support;

final class Url
{
    public static function admin_page(string $slug, array $args = []): string
    {
        return add_query_arg($args, admin_url('admin.php?page=' . $slug));
    }

    public static function action(string $action, array $args = []): string
    {
        // Cho admin-post.php (POST action)
        $base = admin_url('admin-post.php');
        $args = array_merge(['action' => $action], $args);
        return add_query_arg($args, $base);
    }
}
