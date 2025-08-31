<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\Ajax;

use TMT\CRM\Shared\Container;
use TMT\CRM\Domain\Repositories\CompanyRepositoryInterface;
use TMT\CRM\Infrastructure\Security\Capability;

defined('ABSPATH') || exit;

final class CompanyAjaxController
{
    /** Gọi trong bootstrap (file chính) */
    public static function bootstrap(): void // ( file chính )
    {
        add_action('wp_ajax_tmt_crm_search_companies', [self::class, 'search_companies']);
        add_action('wp_ajax_tmt_crm_get_company_label', [self::class, 'get_company_label']);

        // Nếu cần dùng ở front-end cho khách vãng lai thì mở thêm:
        // add_action('wp_ajax_nopriv_tmt_crm_search_companies', [self::class, 'search_companies']);
        // add_action('wp_ajax_nopriv_tmt_crm_get_company_label', [self::class, 'get_company_label']);
    }

    /** Endpoint cho Select2: trả về { results: [{id, text}] } */
    // public static function search_companies(): void
    // {
    //     // Khớp với JS: _ajax_nonce gửi từ wp_localize_script hoặc inline
    //     check_ajax_referer('tmt_crm_select2', '_ajax_nonce');

    //     // Quyền: tuỳ chỉnh theo hệ thống của bạn
    //     if (! current_user_can(Capability::COMPANY_READ)) {
    //         wp_send_json_error(['message' => 'Permission denied'], 403);
    //     }

    //     $q        = isset($_REQUEST['q']) ? sanitize_text_field(wp_unslash($_REQUEST['q'])) : '';
    //     $page     = max(1, (int)($_REQUEST['page'] ?? 1));
    //     $per_page = 20;
    //     $offset   = ($page - 1) * $per_page;

    //     /** @var CompanyRepositoryInterface $repo */
    //     $repo = Container::get('company-repo');

    //     // Tìm kiếm linh hoạt theo khả năng của Repository
    //     $items = [];
    //     if (method_exists($repo, 'search_by_keyword')) {
    //         // Ưu tiên: search_by_keyword(string $q, int $limit, int $offset): array
    //         $items = $repo->search_by_keyword($q, $per_page, $offset);
    //     } elseif (method_exists($repo, 'find_like_name_or_tax_code')) {
    //         // Phương án B: theo tên/MST
    //         $items = $repo->find_like_name_or_tax_code($q, $per_page, $offset);
    //     } elseif (method_exists($repo, 'find_by_name')) {
    //         // Tối thiểu: theo tên
    //         $items = $repo->find_by_name($q, $per_page, $offset);
    //     } else {
    //         // Không có API tìm kiếm → trả rỗng
    //         $items = [];
    //     }

    //     // Chuẩn hoá về [{id, text}]
    //     $results = [];
    //     foreach ($items as $it) {
    //         // Hỗ trợ cả DTO object lẫn array
    //         $id   = is_array($it) ? (int)($it['id'] ?? 0) : (int)($it->id ?? 0);
    //         $name = is_array($it) ? (string)($it['name'] ?? '') : (string)($it->name ?? '');

    //         // Thêm mã số thuế/phone nếu có, để người dùng dễ phân biệt
    //         $tax  = is_array($it) ? ($it['tax_code'] ?? null) : ($it->tax_code ?? null);
    //         $ph   = is_array($it) ? ($it['phone'] ?? null)    : ($it->phone ?? null);

    //         $label = $name;
    //         if ($tax) {
    //             $label .= ' — MST: ' . $tax;
    //         } elseif ($ph) {
    //             $label .= ' — ' . $ph;
    //         }

    //         if ($id > 0 && $label !== '') {
    //             $results[] = [
    //                 'id'   => (string)$id,
    //                 'text' => $label,
    //             ];
    //         }
    //     }

    //     wp_send_json(['results' => $results]);
    // }



    public static function search_companies(): void
    {
        // Khớp với JS: _ajax_nonce gửi từ wp_localize_script hoặc inline
        check_ajax_referer('tmt_crm_select2', '_ajax_nonce');

        // Quyền
        if (! current_user_can(Capability::COMPANY_READ)) {
            wp_send_json_error(['message' => 'Permission denied'], 403);
        }

        // Select2 thường gửi 'term'; vẫn hỗ trợ 'q' cho linh hoạt
        $raw = $_REQUEST['term'] ?? ($_REQUEST['q'] ?? '');
        $q   = sanitize_text_field(wp_unslash((string) $raw));

        $page     = max(1, (int) ($_REQUEST['page'] ?? 1));
        $per_page = 20;

        /** @var CompanyRepositoryInterface $repo */
        $repo = Container::get('company-repo');

        // Dùng đúng API có sẵn trong Repository
        $res = ['items' => [], 'total' => 0];
        if (method_exists($repo, 'search_for_select')) {
            $res = $repo->search_for_select($q, $page, $per_page);
        }

        // Chuẩn hoá [{id, text}] cho Select2
        $items = [];
        foreach ($res['items'] as $row) {
            $id   = (int) ($row['id']   ?? 0);
            $name = (string) ($row['name'] ?? '');

            if ($id > 0 && $name !== '') {
                // Có thể bổ sung thông tin phụ nếu Repository trả về (tuỳ chọn)
                // $tax = $row['tax_code'] ?? null;
                // $ph  = $row['phone']    ?? null;
                // $label = $name . ($tax ? ' — MST: ' . $tax : ($ph ? ' — ' . $ph : ''));

                $items[] = [
                    'id'   => (string) $id,
                    'text' => $name, // giữ nhẹ cho Select2
                ];
            }
        }

        // Tính pagination.more theo tổng
        $total = (int) ($res['total'] ?? 0);
        $more  = ($page * $per_page) < $total;

        wp_send_json([
            'results'    => $items,
            'pagination' => ['more' => $more],
        ]);
    }




    /** Lấy nhãn hiển thị từ 1 ID (prefill) → trả {id, text} */
    public static function get_company_label(): void
    {
        check_ajax_referer('tmt_crm_select2', '_ajax_nonce');

        if (! current_user_can(Capability::COMPANY_READ)) {
            wp_send_json_error(['message' => 'Permission denied'], 403);
        }

        $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
        if ($id <= 0) {
            wp_send_json(['id' => 0, 'text' => '']);
        }

        /** @var CompanyRepositoryInterface $repo */
        $repo = Container::get('company-repo');

        $name = '';
        if (method_exists($repo, 'find_name_by_id')) {
            $name = (string)($repo->find_name_by_id($id) ?? '');
        } elseif (method_exists($repo, 'find_by_id')) {
            $obj  = $repo->find_by_id($id);
            $name = (string)($obj->name ?? '');
        }

        wp_send_json(['id' => $id, 'text' => $name]);
    }
}
