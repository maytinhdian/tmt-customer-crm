<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Contact\Presentation\Admin\Ajax;

use TMT\CRM\Modules\Contact\Domain\Repositories\CompanyContactRepositoryInterface;
use TMT\CRM\Shared\Container;

final class CompanyContactAjaxController
{
    public static function register(): void
    {
        add_action('wp_ajax_tmt_crm_get_primary_contact_by_company', [self::class, 'get_primary_contact_by_company']);
        add_action('wp_ajax_tmt_crm_search_contacts_by_company', [self::class, 'search_contacts_by_company']);
    }

    /** Lấy liên hệ chính của 1 công ty */
    public static function get_primary_contact_by_company(): void
    {
        check_ajax_referer('tmt_crm_select2', 'nonce');

        $company_id = isset($_GET['company_id']) ? absint($_GET['company_id']) : 0;
        if ($company_id <= 0) {
            wp_send_json_error(['message' => 'Thiếu company_id'], 400);
        }

        /** @var CompanyContactRepositoryInterface $repo */
        $repo = Container::get(CompanyContactRepositoryInterface::class);
        $contact = $repo->find_active_primary_by_company($company_id);

        if (!$contact) {
            wp_send_json_success(null); // công ty chưa có liên hệ chính
        }

        wp_send_json_success([
            'id'   => $contact->id,
            'text' => $contact->full_name . ' — ' . $contact->phone,
            'phone' => $contact->phone,
            'email' => $contact->email,
        ]);
    }

    /** Search liên hệ trong phạm vi 1 công ty */
    public static function search_contacts_by_company(): void
    {
        check_ajax_referer('tmt_crm_select2', 'nonce');

        $company_id = isset($_GET['company_id']) ? absint($_GET['company_id']) : 0;
        $term       = sanitize_text_field($_GET['term'] ?? '');
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $per_page   = 20;

        if ($company_id <= 0) {
            wp_send_json_success(['results' => []]);
        }

        /** @var CompanyContactRepositoryInterface $repo */
        $repo   = Container::get(CompanyContactRepositoryInterface::class);
        $result = $repo->find_by_company($company_id, $term, $page, $per_page);

        $items = array_map(function ($row) {
            return [
                'id'   => $row->id,
                'text' => $row->full_name . ' — ' . $row->phone,
            ];
        }, $result['items'] ?? []);

        $more = ($page * $per_page) < ($result['total'] ?? 0);

        wp_send_json_success([
            'results'    => $items,
            'pagination' => ['more' => $more],
        ]);
    }
}
