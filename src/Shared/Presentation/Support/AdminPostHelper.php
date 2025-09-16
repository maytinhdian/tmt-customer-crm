<?php
declare(strict_types=1);

namespace TMT\CRM\Shared\Presentation\Support;
/**
 * AdminPostHelper: Chuẩn hoá việc sinh URL/hidden fields cho admin-post.php
 * - Dùng cho cả GET (link thao tác nhanh) lẫn POST (form submit).
 * - Hỗ trợ append state (paged, orderby, ...) để sau thao tác quay về đúng vị trí.
 * - Tự chèn nonce (tuỳ chọn) và _wp_http_referer.
 */
final class AdminPostHelper
{
    /**
     * Tạo URL đến admin-post.php (thường dùng cho link GET như: set_primary, delete …)
     *
     * @param string      $action         Tên action WordPress (ví dụ: 'tmt_crm_company_contact_set_primary')
     * @param array       $args           Tham số bổ sung (vd: ['contact_id'=>12, 'company_id'=>3])
     * @param string|null $nonce_action   Tên action cho nonce (vd: 'set_primary_contact_12'), null = không chèn nonce
     * @param string      $nonce_name     Tên field nonce (mặc định: _wpnonce)
     * @param string[]    $state_whitelist Danh sách key state giữ lại từ $_GET (vd: ['paged','orderby','order','tab'])
     */
    public static function url(
        string $action,
        array $args = [],
        ?string $nonce_action = null,
        string $nonce_name = '_wpnonce',
        array $state_whitelist = ['paged', 'orderby', 'order', 'tab']
    ): string {
        // Giữ state hiện tại (nếu có)
        foreach ($state_whitelist as $key) {
            if (isset($_GET[$key]) && $_GET[$key] !== '') {
                // Với state, ta chỉ cần sanitize text
                $args[$key] = sanitize_text_field(wp_unslash((string) $_GET[$key]));
            }
        }

        // Bắt buộc có action
        $args['action'] = $action;

        // Nonce (nếu truyền vào)
        if (!empty($nonce_action)) {
            $args[$nonce_name] = wp_create_nonce($nonce_action);
        }

        // Thêm referer để quay lại sau khi xử lý
        if (empty($args['_wp_http_referer'])) {
            $args['_wp_http_referer'] = self::currentRequestUri();
        }

        return add_query_arg($args, admin_url('admin-post.php'));
    }

    /**
     * Sinh các hidden fields cho form POST về admin-post.php
     * - Dùng thay cho việc tự viết <input type="hidden" ...> lắt nhắt.
     */
    public static function hiddenFields(
        string $action,
        array $args = [],
        ?string $nonce_action = null,
        string $nonce_name = '_wpnonce'
    ): string {
        $html  = '<input type="hidden" name="action" value="' . esc_attr($action) . '">' . "\n";

        if (!empty($nonce_action)) {
            $html .= '<input type="hidden" name="' . esc_attr($nonce_name) . '" value="' . esc_attr(wp_create_nonce($nonce_action)) . '">' . "\n";
        }

        if (empty($args['_wp_http_referer'])) {
            $args['_wp_http_referer'] = self::currentRequestUri();
        }

        foreach ($args as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $html .= '<input type="hidden" name="' . esc_attr((string) $k) . '" value="' . esc_attr((string) $v) . '">' . "\n";
        }

        return $html;
    }

    /**
     * Lấy URL hiện tại (để làm referer).
     * Ưu tiên REQUEST_URI vì screen admin có đủ query hiện tại.
     */
    public static function currentRequestUri(): string
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        if ($uri === '') {
            // Fallback
            return admin_url('admin.php');
        }
        // Không esc_url ở đây, chỉ trả raw để add vào hidden; sẽ được WP xử lý khi redirect.
        return $uri;
    }
}

// Cách dùng :

// use TMT\CRM\Presentation\Support\AdminPostHelper;

// // Đặt làm liên hệ chính
// $set_primary_url = AdminPostHelper::url(
//     'tmt_crm_company_contact_set_primary',
//     [
//         'contact_id' => (int) $item->id,
//         'company_id' => (int) $item->company_id,
//     ],
//     // nonce_action nên gắn id để unique
//     'set_primary_contact_' . (int) $item->id
// );

// // Xoá mềm
// $delete_url = AdminPostHelper::url(
//     'tmt_crm_company_contact_delete',
//     [
//         'id'         => (int) $item->id,
//         'company_id' => (int) $item->company_id,
//     ],
//     'delete_company_contact_' . (int) $item->id
// );


// declare(strict_types=1);

// namespace TMT\CRM\Presentation\Admin\Support;

// final class AdminPostHelper
// {
//     public static function url(string $action, array $args, string $nonce_action): string
//     {
//         $url = add_query_arg(array_merge(['action' => $action], $args), admin_url('admin-post.php'));
//         return wp_nonce_url($url, $nonce_action);
//     }
// }