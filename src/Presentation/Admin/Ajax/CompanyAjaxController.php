<?php
declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\Ajax;

use TMT\CRM\Shared\Container;
use TMT\CRM\Domain\Repositories\CompanyRepositoryInterface;

final class CompanyAjaxController
{
    public static function bootstrap(): void // (file chính) gọi hàm này khi admin init
    {

        add_action('wp_ajax_tmt_crm_search_companies', [self::class, 'search_companies']);
        add_action('wp_ajax_tmt_crm_get_company_label', [self::class, 'get_company_label']); // preload 1 id -> text
    }

    public static function search_companies(): void
    {
        check_ajax_referer('tmt_crm_select2_nonce', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'forbidden'], 403);
        }

        $term = isset($_GET['term']) ? sanitize_text_field((string)$_GET['term']) : '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

        /** @var CompanyRepositoryInterface $repo */
        $repo = Container::get('company-repo');

        $res = $repo->search_for_select($term, $page, 20);

        $items = array_map(static function(array $row): array {
            return [
                'id'   => (int)$row['id'],
                'text' => (string)$row['name'],
            ];
        }, $res['items']);

        $more = ($page * 20) < $res['total'];

        wp_send_json([
            'results'    => $items,
            'pagination' => ['more' => $more],
        ]);
    }

    public static function get_company_label(): void
    {
        check_ajax_referer('tmt_crm_select2_nonce', 'nonce');

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            wp_send_json_success(['id' => 0, 'text' => '']);
        }

        /** @var CompanyRepositoryInterface $repo */
        $repo = Container::get('company-repo');
        $name = (string)($repo->find_name_by_id($id) ?? '');

        wp_send_json_success(['id' => $id, 'text' => $name]);
    }
}
