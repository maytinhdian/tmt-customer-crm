<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\Ajax;

final class UserAjaxController
{
    public static function bootstrap(): void
    {
        add_action('wp_ajax_tmt_crm_search_users', [self::class, 'search_users']);
        add_action('wp_ajax_tmt_crm_get_user_label', [self::class, 'get_user_label']);
    }

    public static function search_users(): void
    {
        check_ajax_referer('tmt_crm_select2_nonce', 'nonce');

        if (!current_user_can('list_users')) {
            wp_send_json_error(['message' => 'forbidden'], 403);
        }

        $term = isset($_GET['term']) ? sanitize_text_field((string)$_GET['term']) : '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $per_page = 20;

        $args = [
            'number'  => $per_page,
            'offset'  => ($page - 1) * $per_page,
            'search'  => '*' . $term . '*',
            'fields'  => ['ID', 'display_name', 'user_email'],
            'orderby' => 'display_name',
            'order'   => 'ASC',
        ];
        $users = get_users($args);
        $total = count_users()['total_users'] ?? 0;

        $items = array_map(static function ($u): array {
            $label = trim($u->display_name . ' — ' . $u->user_email);
            return ['id' => (int)$u->ID, 'text' => $label];
        }, $users);

        $more = ($page * $per_page) < (int)$total;

        wp_send_json([
            'results'    => $items,
            'pagination' => ['more' => $more],
        ]);
    }

    public static function get_user_label(): void
    {
        check_ajax_referer('tmt_crm_select2_nonce', 'nonce');

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            wp_send_json_success(['id' => 0, 'text' => '']);
        }
        $u = get_user_by('ID', $id);
        $label = $u ? trim($u->display_name . ' — ' . $u->user_email) : '';
        wp_send_json_success(['id' => $id, 'text' => $label]);
    }
}
