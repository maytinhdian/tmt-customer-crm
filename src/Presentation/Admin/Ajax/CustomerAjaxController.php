<?php
declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\Ajax;

use TMT\CRM\Shared\Container;
use TMT\CRM\Domain\Repositories\CustomerRepositoryInterface;

final class CustomerAjaxController
{
    public static function bootstrap(): void // (file chính) gọi hàm này
    {
        add_action('wp_ajax_tmt_crm_search_customers', [self::class, 'search_customers']);
        add_action('wp_ajax_tmt_crm_get_customer_label', [self::class, 'get_customer_label']);
        // Nếu cần dùng ở frontend cho khách chưa đăng nhập:
        // add_action('wp_ajax_nopriv_tmt_crm_search_customers', [self::class, 'search_customers']);
        // add_action('wp_ajax_nopriv_tmt_crm_get_customer_label', [self::class, 'get_customer_label']);
    }

    public static function search_customers(): void
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

        /** @var CustomerRepositoryInterface $repo */
        $repo = Container::get('customer-repo');

        $res = $repo->search_for_select($term, $page, $per_page); // ⬅️ mục 3
        $items = array_map(static function(array $row): array {
            return [
                'id'   => (int)$row['id'],
                'text' => (string)$row['name'], // hiển thị tên
            ];
        }, $res['items']);

        wp_send_json([
            'results'    => $items,
            'pagination' => ['more' => !empty($res['more'])],
        ]);
    }

    public static function get_customer_label(): void
    {
        if (false === check_ajax_referer('tmt_crm_select2_nonce', 'nonce', false)) {
            wp_send_json_error(['code' => 'bad_nonce'], 403);
        }
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            wp_send_json_success(['id' => 0, 'text' => '']);
        }

        /** @var CustomerRepositoryInterface $repo */
        $repo = Container::get('customer-repo');
        $name = (string)($repo->find_name_by_id($id) ?? '');

        wp_send_json_success(['id' => $id, 'text' => $name]);
    }
}
