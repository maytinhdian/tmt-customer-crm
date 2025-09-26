<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Customer\Presentation\Admin\Ajax;

use TMT\CRM\Modules\Customer\Domain\Repositories\CustomerRepositoryInterface;
use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Capabilities\Domain\Capability;

final class CustomerAjaxController
{
    /**
     * Gọi trong bootstrap (file chính) để đăng ký AJAX action.
     * (bootstrap – file chính)
     */
    public static function bootstrap(): void
    {
        add_action('wp_ajax_tmt_crm_search_customers', [self::class, 'search_customers']);
        add_action('wp_ajax_tmt_crm_get_customer_label', [self::class, 'get_customer_label']);

        // Nếu cần cho người dùng chưa đăng nhập (frontend), bỏ comment:
        // add_action('wp_ajax_nopriv_tmt_crm_search_customers', [self::class, 'search_customers']);
        // add_action('wp_ajax_nopriv_tmt_crm_get_customer_label', [self::class, 'get_customer_label']);
    }

    /**
     * AJAX: Tìm khách hàng cho Select2.
     * expects: $_GET['term'], $_GET['page'], $_GET['nonce']
     * returns (success): { success:true, data:{ results:[{id,text}], pagination:{more:bool} } }
     */
    public static function search_customers(): void
    {
        check_ajax_referer('tmt_crm_select2_nonce', 'nonce');

        // Chặn quyền nếu cần (giống Owner)
        if (!current_user_can(Capability::CUSTOMER_READ) && !current_user_can('read')) {
            wp_send_json_error(['message' => 'forbidden'], 403);
        }

        $term     = isset($_GET['term']) ? sanitize_text_field((string)$_GET['term']) : '';
        $page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $per_page = 20;

        /** @var CustomerRepositoryInterface $repo */
        $repo = Container::get('customer-repo');

        // Repo trả về ['items' => [['id'=>..,'name'=>..], ...], 'total' => int]
        $res   = $repo->search_for_select($term, $page, $per_page);
        $items = array_map(static function (array $row): array {
            return [
                'id'   => (int)($row['id'] ?? 0),
                'text' => (string)($row['name'] ?? ''),
            ];
        }, $res['items'] ?? []);

        $total = (int)($res['total'] ?? 0);
        $more  = ($page * $per_page) < $total;

        // Quan trọng: dùng wp_send_json_success, KHÔNG set status=400
        wp_send_json_success([
            'results'    => $items,
            'pagination' => ['more' => $more],
        ]);
    }

    /**
     * AJAX: Lấy nhãn ban đầu theo ID (Select2)
     * expects: $_GET['id'], $_GET['nonce']
     * returns (success): { success:true, data:{ id:number, text:string } }
     */
    public static function get_customer_label(): void
    {
        check_ajax_referer('tmt_crm_select2_nonce', 'nonce');

        if (!current_user_can(Capability::CUSTOMER_READ) && !current_user_can('read')) {
            wp_send_json_error(['message' => 'forbidden'], 403);
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            wp_send_json_success(['id' => 0, 'text' => '']);
        }

        /** @var CustomerRepositoryInterface $repo */
        $repo  = Container::get('customer-repo');
        $label = $repo->get_label($id);

        wp_send_json_success([
            'id'   => $id,
            'text' => (string)($label ?? ''),
        ]);
    }
}
