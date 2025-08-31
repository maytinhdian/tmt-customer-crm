<?php

namespace TMT\CRM\Presentation\Admin\Ajax;

use TMT\CRM\Infrastructure\Security\Capability;
use TMT\CRM\Domain\Repositories\CustomerRepositoryInterface;  // use TMT\CRM\Domain\Repositories\
use TMT\CRM\Shared\Container;


final class QuoteAjaxController
{
    public static function register(): void
    {
        add_action('wp_ajax_tmt_crm_customer_search', [self::class, 'customer_search']);
        // admin page nên không cần nopriv
    }

    /** @return void */
    public static function customer_search(): void
    {
        // Bảo vệ
        check_ajax_referer('tmt_crm_ajax', 'nonce');

        // Tuỳ hệ thống quyền của bạn:
        if (! current_user_can(Capability::CUSTOMER_READ)) {
            wp_send_json_error(['message' => __('Không đủ quyền', 'tmt-crm')], 403);
        }

        $q    = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per  = 20;
        $offset = ($page - 1) * $per;

        /** @var CustomerRepositoryInterface $repo */
        $repo = Container::get('customer-repo');

        // Giả định repo có hàm tìm theo tên công ty/khách hàng, trả về DTO (id, full_name, company_name)
        $rows = $repo->search_customers_with_company($q, $per, $offset);

        $results = array_map(static function ($dto) {
            // ƯU TIÊN tên công ty làm nhãn chính
            $company = (string) ($dto->company_name ?? '');
            $contact = (string) ($dto->full_name ?? '');
            return [
                'id'      => (int) $dto->id,
                'text'    => $company ?: $contact, // fallback
                'company' => $company,
                'contact' => $contact,
            ];
        }, $rows);

        // Nếu repo có total, bạn có thể báo còn trang sau
        $has_more = (count($results) === $per);

        wp_send_json([
            'results' => $results,
            'more'    => $has_more,
        ]);
    }
}
