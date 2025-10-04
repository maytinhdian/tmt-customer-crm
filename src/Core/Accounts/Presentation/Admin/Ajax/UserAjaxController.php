<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Accounts\Presentation\Admin\Ajax;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Accounts\Application\Services\UserService;
use TMT\CRM\Core\Accounts\Application\Services\AccountPolicyService;

final class UserAjaxController
{
    public static function bootstrap(): void
    {
        add_action('wp_ajax_tmt_crm_search_users', [self::class, 'handle_search']);
        add_action('wp_ajax_tmt_crm_user_label', [self::class, 'handle_label']);
    }

    public static function handle_search(): void
    {
        if (!AccountPolicyService::can_use_picker()) {
            wp_send_json_error(['message' => __('Not allowed', 'tmt-crm')], 403);
        }
        check_ajax_referer('tmt_crm_user_picker');

        $q          = isset($_GET['q']) ? sanitize_text_field((string)$_GET['q']) : '';
        $page       = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $per_page   = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 20;
        $must_cap   = isset($_GET['must_cap']) ? sanitize_text_field((string)$_GET['must_cap']) : '';

        /** @var UserService $svc */
        $svc = Container::get(UserService::class);
        $data = $svc->search_for_select2($q, $page, $per_page, $must_cap);

        wp_send_json_success($data);
    }

    public static function handle_label(): void
    {
        if (!AccountPolicyService::can_use_picker()) {
            wp_send_json_error(['message' => __('Not allowed', 'tmt-crm')], 403);
        }
        check_ajax_referer('tmt_crm_user_picker');

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            wp_send_json_error(['message' => 'Invalid id'], 400);
        }

        /** @var UserService $svc */
        $svc = Container::get(UserService::class);
        $label = $svc->label_by_id($id);

        if ($label === null) {
            wp_send_json_error(['message' => 'Not found'], 404);
        } else {
            wp_send_json_success(['label' => $label]);
        }
    }
}
