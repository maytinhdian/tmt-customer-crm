<?php
// src/Presentation/Support/AdminPostUrl.php
namespace TMT\CRM\Presentation\Support;

final class AdminPostUrl
{
    /**
     * Tạo URL tới admin-post.php?action=... + args và tự thêm nonce.
     * $nonce_action mặc định chính là $action.
     */
    public static function action(string $action, array $args = [], bool $with_nonce = true, ?string $nonce_action = null, string $nonce_name = '_wpnonce'): string
    {
        $base = admin_url('admin-post.php');
        $query = array_merge(['action' => $action], $args);

        if ($with_nonce) {
            $query[$nonce_name] = wp_create_nonce($nonce_action ?: $action);
        }
        return add_query_arg($query, $base);
    }
}
