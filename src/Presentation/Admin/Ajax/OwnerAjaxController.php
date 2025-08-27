<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\Ajax;

use TMT\CRM\Shared\Container;
use TMT\CRM\Domain\Repositories\UserRepositoryInterface;
use TMT\CRM\Infrastructure\Security\Capability;

final class OwnerAjaxController
{
    public static function bootstrap(): void // (file chính) gọi hàm này
    {
        add_action('wp_ajax_tmt_crm_search_owners', [self::class, 'search']);
        add_action('wp_ajax_tmt_crm_get_owner_label', [self::class, 'get_label']);

        // nếu cần dùng ở frontend cho khách chưa đăng nhập:
        // add_action('wp_ajax_nopriv_tmt_crm_search_owners', [self::class, 'search']);
        // add_action('wp_ajax_nopriv_tmt_crm_get_owner_label', [self::class, 'get_label']);
    }

    public static function search(): void
    {
        if (false === check_ajax_referer('tmt_crm_select2_nonce', 'nonce', false)) {
            wp_send_json_error(['code' => 'bad_nonce'], 403);
        }
        if (!current_user_can('read')) {
            wp_send_json_error(['code' => 'forbidden'], 403);
        }

        $term = isset($_GET['term']) ? sanitize_text_field((string)$_GET['term']) : '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $per_page = 20;

        /** @var UserRepositoryInterface $repo */
        $repo = Container::get('user-repo');

        // Bắt buộc phải có capability COMPANY_CREATE
        $cap = defined(Capability::class . '::COMPANY_CREATE')
            ? Capability::COMPANY_CREATE
            : 'manage_tmt_crm_companies'; // fallback nếu bạn chưa định nghĩa hằng này

        $res = $repo->search_for_select($term, $page, $per_page, $cap);

        $items = array_map(static function (array $row): array {
            return ['id' => (int)$row['id'], 'text' => (string)$row['label']];
        }, $res['items']);

        wp_send_json([
            'results'    => $items,
            'pagination' => ['more' => !empty($res['more'])],
        ]);
    }

    public static function get_label(): void
    {
        if (false === check_ajax_referer('tmt_crm_select2_nonce', 'nonce', false)) {
            wp_send_json_error(['code' => 'bad_nonce'], 403);
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            wp_send_json_success(['id' => 0, 'text' => '']);
        }

        /** @var \TMT\CRM\Domain\Repositories\UserRepositoryInterface $repo */
        $repo  = \TMT\CRM\Shared\Container::get('user-repo');

        // ⚠️ KHÔNG kiểm tra capability ở đây để vẫn load được “người quản lý cũ”
        $label = $repo->find_label_by_id($id);
        if ($label === null) {
            $label = sprintf(__('(Người dùng #%d đã bị xoá)', 'tmt-crm'), $id);
        }

        wp_send_json_success(['id' => $id, 'text' => $label]);
    }
}
