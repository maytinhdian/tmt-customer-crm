<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\Company\Form;

use TMT\CRM\Shared\Container;
use TMT\CRM\Domain\ValueObject\CompanyContactRole;
use TMT\CRM\Application\Services\CompanyContactService;
use TMT\CRM\Domain\Repositories\CompanyContactRepositoryInterface;
use TMT\CRM\Infrastructure\Security\Capability; // có thể bỏ nếu bạn chưa có

defined('ABSPATH') || exit;

/**
 * CompanyContactsBox
 * - Hiển thị danh sách liên hệ (contact) theo role của Company
 * - Form thêm liên hệ mới
 * - Xử lý các action: add / end / set_primary / delete
 *
 * Quy ước:
 * - Nonce action: tmt_crm_company_contacts
 * - Capability: 'manage_tmt_crm_companies' (hoặc Capability::EDIT / Capability::DELETE nếu có)
 */
final class CompanyContactsBox
{
    /** Slug trang customers trên admin.php?page=... */
    public const PAGE_SLUG = 'tmt-crm-company-contacts';

    // /** Render box trong form công ty */
    // public static function render(int $company_id): void
    // {
    //     // Quyền xem/list Company
    //     if (! current_user_can(Capability::COMPANY_READ)) {
    //         wp_die(
    //             '<p><em>' . esc_html__('Bạn không có quyền xem mục này.', 'tmt-crm') . '</em></p>',
    //             esc_html__('Truy cập bị từ chối', 'tmt-crm'),
    //             ['response' => 403]
    //         );
    //     }
    //     /** @var CompanyContactRepositoryInterface $repo */
    //     $repo = Container::get('company-contact-repo');
    //     $roles = CompanyContactRole::all();

    //     // Lấy tất cả contact đang active (có thể nhóm theo role ở template)
    //     $contacts = $repo->find_active_contacts_by_company($company_id, null);

    //     // Prefetch tên khách hàng để tránh N+1 (nếu repo có hỗ trợ); nếu không, sẽ fallback lấy lẻ
    //     $customerLabels = self::prefetch_customer_labels($contacts);

    //     // $nonce = wp_create_nonce('tmt_crm_company_contacts');
    //     $action_add = admin_url('admin-post.php?action=tmt_crm_company_add_contact');
    //     $action_end = admin_url('admin-post.php?action=tmt_crm_company_end_contact');
    //     $action_primary = admin_url('admin-post.php?action=tmt_crm_company_set_primary');
    //     $action_delete = admin_url('admin-post.php?action=tmt_crm_company_delete_contact');

    //     include TMT_CRM_PATH . 'templates/admin/partials/company-contacts-box.php';
    // }

    public static function render(int $company_id): void
    {
        // Quyền xem khối này (tuỳ bạn áp dụng)
        if (! current_user_can(Capability::COMPANY_READ)) {
            echo '<p><em>' . esc_html__('Bạn không có quyền xem mục này.', 'tmt-crm') . '</em></p>';
            return;
        }

        /** @var CompanyContactRepositoryInterface $contact_repo */
        $contact_repo = Container::get('company-contact-repo');

        /** @var CustomerRepositoryInterface $customer_repo */
        $customer_repo = Container::get('customer-repo');

        // 1) Dữ liệu đang có: danh sách contact đang active của company
        // (tham số 2 là role filter, để null để lấy tất cả)
        $contacts = $contact_repo->find_active_contacts_by_company($company_id, null);

        // 2) Danh sách role hiển thị/đổ vào select (tuỳ cách bạn lưu role)
        $roles = CompanyContactRole::all(); // hoặc mảng tĩnh của bạn

        // 3) Danh sách khách hàng (customers) để chọn thêm vào công ty
        // Tuỳ API repo của bạn. Ví dụ:
        // $customer_choices = $customer_repo->find_all_active_for_choices();
        // Ở đây mình trả về mảng [id => 'Họ tên / email / phone']
        $customer_choices = method_exists($customer_repo, 'find_all_active_for_choices')
            ? $customer_repo->find_all_active_for_choices()
            : [];

        // 4) Sticky form: ưu tiên giữ lại giá trị người dùng vừa submit
        // (tránh warning: luôn set biến trước khi include)
        $customer_id_selected = isset($_POST['customer_id'])
            ? (int) wp_unslash($_POST['customer_id'])
            : 0;

        // 5) Các action xử lý form (thống nhất với CompanyScreen)
        $action_add     = admin_url('admin-post.php?action=tmt_crm_company_add_contact');
        $action_end     = admin_url('admin-post.php?action=tmt_crm_company_end_contact');
        $action_primary = admin_url('admin-post.php?action=tmt_crm_company_set_primary');

        // Alias để template cũ dùng $action_delete vẫn chạy OK
        $action_delete  = $action_end;

        // 6) Nonce tuỳ mỗi action (nếu template dùng)
        $nonce_add     = wp_create_nonce('tmt_crm_company_add_contact_' . $company_id);
        $nonce_end     = wp_create_nonce('tmt_crm_company_end_contact_' . $company_id);
        $nonce_primary = wp_create_nonce('tmt_crm_company_set_primary_' . $company_id);
        
        // Alias nonce cho delete nếu template dùng
        $nonce_delete   = $nonce_end;

        // 7) Include template: lúc này tất cả biến trên đã tồn tại trong scope
        $tpl = trailingslashit(TMT_CRM_PATH) . 'templates/admin/partials/company-contacts-box.php';
        include $tpl;
    }

    /** ====== HANDLERS ====== */

    /** POST: thêm/sửa liên hệ (assign) */
    public static function handle_add_contact(): void
    {
        // self::guard_cap_nonce('tmt_crm_company_contacts');

        $company_id  = isset($_POST['company_id'])  ? absint($_POST['company_id']) : 0;
        $customer_id = isset($_POST['customer_id']) ? absint($_POST['customer_id']) : 0;
        $role        = isset($_POST['role'])        ? sanitize_key($_POST['role']) : '';
        $title       = isset($_POST['title'])       ? sanitize_text_field($_POST['title']) : null;
        $is_primary  = !empty($_POST['is_primary']);
        $start_date  = isset($_POST['start_date'])  ? sanitize_text_field($_POST['start_date']) : null;
        $note        = isset($_POST['note'])        ? wp_kses_post($_POST['note']) : null;
        $redirect    = self::get_referer_fallback();

        if ($company_id <= 0 || $customer_id <= 0 || !\TMT\CRM\Domain\ValueObject\CompanyContactRole::is_valid($role)) {
            wp_safe_redirect(add_query_arg(['cc_msg' => 'invalid'], $redirect));
            exit;
        }

        /** @var CompanyContactService $svc */
        $svc = Container::get('company-contact-service');
        $svc->save_contact(
            dto: new \TMT\CRM\Application\DTO\CompanyContactDTO(
                id: null,
                company_id: $company_id,
                customer_id: $customer_id,
                role: $role,
                title: $title,
                is_primary: $is_primary,
                start_date: $start_date,
                end_date: null,
                note: $note,
                created_at: null,
                updated_at: null
            ),
            set_primary: $is_primary
        );

        wp_safe_redirect(add_query_arg(['cc_msg' => 'saved'], $redirect));
        exit;
    }

    /** POST: kết thúc liên hệ (set end_date) */
    public static function handle_end_contact(): void
    {
        self::guard_cap_nonce('tmt_crm_company_contacts');

        $contact_id = isset($_POST['contact_id']) ? absint($_POST['contact_id']) : 0;
        $end_date   = isset($_POST['end_date'])   ? sanitize_text_field($_POST['end_date']) : date('Y-m-d');
        $redirect   = self::get_referer_fallback();

        if ($contact_id <= 0) {
            wp_safe_redirect(add_query_arg(['cc_msg' => 'invalid'], $redirect));
            exit;
        }

        /** @var CompanyContactService $svc */
        $svc = Container::get('company-contact-service');
        $svc->end_contact($contact_id, $end_date);

        wp_safe_redirect(add_query_arg(['cc_msg' => 'ended'], $redirect));
        exit;
    }

    /** POST: đặt liên hệ là primary cho role */
    public static function handle_set_primary(): void
    {
        self::guard_cap_nonce('tmt_crm_company_contacts');

        $contact_id = isset($_POST['contact_id']) ? absint($_POST['contact_id']) : 0;
        $redirect   = self::get_referer_fallback();

        if ($contact_id <= 0) {
            wp_safe_redirect(add_query_arg(['cc_msg' => 'invalid'], $redirect));
            exit;
        }

        /** @var CompanyContactService $svc */
        $svc = Container::get('company-contact-service');
        $svc->set_primary_contact($contact_id);

        wp_safe_redirect(add_query_arg(['cc_msg' => 'primary_set'], $redirect));
        exit;
    }

    /** POST: xoá cứng liên hệ (tuỳ chính sách dữ liệu) */
    public static function handle_delete_contact(): void
    {
        self::guard_cap_nonce('tmt_crm_company_contacts');

        $contact_id = isset($_POST['contact_id']) ? absint($_POST['contact_id']) : 0;
        $redirect   = self::get_referer_fallback();

        if ($contact_id <= 0) {
            wp_safe_redirect(add_query_arg(['cc_msg' => 'invalid'], $redirect));
            exit;
        }

        /** @var CompanyContactRepositoryInterface $repo */
        $repo = Container::get('company-contact-repo');
        $repo->delete($contact_id);

        wp_safe_redirect(add_query_arg(['cc_msg' => 'deleted'], $redirect));
        exit;
    }


    /* ===================== Helpers ===================== */

    /** Build URL admin.php?page=tmt-crm-customers + $args */
    private static function url(array $args = []): string
    {
        $base = admin_url('admin.php');
        $args = array_merge(['page' => self::PAGE_SLUG], $args);
        return add_query_arg($args, $base);
    }

    /** Kiểm tra quyền, nếu không đủ -> die với thông báo */
    private static function ensure_capability(string $capability, string $message): void
    {
        if (!current_user_can($capability)) {
            wp_die($message);
        }
    }

    /** Redirect & exit */
    private static function redirect(string $url): void
    {
        wp_safe_redirect($url);
        exit;
    }
    private static function guard_cap_nonce(string $nonce_action): void
    {
        if (!current_user_can('manage_tmt_crm_companies')) {
            wp_die(__('Bạn không có quyền thao tác mục này.', 'tmt-crm'), 403);
        }
        check_admin_referer($nonce_action);
    }

    private static function get_referer_fallback(): string
    {
        $ref = wp_get_referer();
        return $ref ?: admin_url('admin.php?page=tmt-crm-companies');
    }

    /**
     * Prefetch tên khách hàng để tránh N+1.
     * Trường hợp repo CHƯA có phương thức batch, hàm này sẽ trả về mảng rỗng
     * và vòng lặp sẽ tự fallback sang get_customer_label() từng cái.
     *
     * @param CompanyContactDTO[] $contacts
     * @return array<int,string> Map [customer_id => "Tên khách hàng (#ID)"]
     */
    private static function prefetch_customer_labels(array $contacts): array
    {
        $ids = [];
        foreach ($contacts as $c) {
            $cid = (int)($c->customer_id ?? 0);
            if ($cid > 0) $ids[$cid] = $cid;
        }
        if (empty($ids)) return [];

        /** @var CustomerRepositoryInterface $customerRepo */
        $customerRepo = Container::get('customer-repo');

        // Nếu có phương thức batch như find_by_ids, dùng nó; nếu không, return []
        if (!method_exists($customerRepo, 'find_by_ids')) {
            return [];
        }

        /** @var CustomerDTO[] $rows */
        $rows = $customerRepo->find_by_ids(array_values($ids));

        $out = [];
        foreach ($rows as $cus) {
            $id    = (int)($cus->id ?? 0);
            $name  = trim((string)($cus->name ?? ''));
            $label = $name !== '' ? ($name . ' (#' . $id . ')') : ('#' . $id);
            if ($id > 0) $out[$id] = $label;
        }
        return $out;
    }

    /**
     * Lấy label cho 1 customer (có cache tĩnh)
     * - Ưu tiên tên khách + kèm (#ID)
     * - Nếu không có tên thì chỉ in #ID
     */
    private static function get_customer_label(int $customer_id): string
    {
        static $cache = [];

        if ($customer_id <= 0) return '—';
        if (isset($cache[$customer_id])) return $cache[$customer_id];

        /** @var CustomerRepositoryInterface $customerRepo */
        $customerRepo = Container::get('customer-repo');
        /** @var CustomerDTO|null $cus */
        $cus = $customerRepo->find_by_id($customer_id);

        $name  = $cus && !empty($cus->name) ? trim((string)$cus->name) : '';
        $label = $name !== '' ? ($name . ' (#' . $customer_id . ')') : ('#' . $customer_id);

        $cache[$customer_id] = $label;
        return $label;
    }
}
