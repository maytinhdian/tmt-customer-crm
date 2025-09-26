<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Password\Presentation\Admin\Controller;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Modules\Password\Application\DTO\PasswordItemDTO;
use TMT\CRM\Modules\Password\Application\Services\PasswordService;
use TMT\CRM\Modules\Password\Presentation\Admin\Screen\PasswordScreen;

final class PasswordController
{
    /** Action names cho admin-post */
    public const ACTION_SAVE        = 'tmt_crm_password_save';
    public const ACTION_SOFT_DELETE = 'tmt_crm_password_soft_delete';
    public const ACTION_RESTORE     = 'tmt_crm_password_restore';

    /** Nonce keys */
    private const NONCE_SAVE        = 'tmt_crm_password_save';
    private const NONCE_SOFT_DELETE = 'tmt_crm_password_soft_delete';
    private const NONCE_RESTORE     = 'tmt_crm_password_restore';

    public static function register(): void
    {
        // Hành vi GET trên màn hình (ví dụ: reveal)
        add_action('admin_init', [self::class, 'handle_actions']);

        // Routes POST (đã đăng nhập)
        add_action('admin_post_' . self::ACTION_SAVE,        [self::class, 'save']);
        add_action('admin_post_' . self::ACTION_SOFT_DELETE, [self::class, 'soft_delete']);
        add_action('admin_post_' . self::ACTION_RESTORE,     [self::class, 'restore']);

        // Chặn khách chưa đăng nhập
        add_action('admin_post_nopriv_' . self::ACTION_SAVE,        [self::class, 'forbid']);
        add_action('admin_post_nopriv_' . self::ACTION_SOFT_DELETE, [self::class, 'forbid']);
        add_action('admin_post_nopriv_' . self::ACTION_RESTORE,     [self::class, 'forbid']);
    }

    /**
     * Xử lý action GET trên screen (reveal password)
     */
    public static function handle_actions(): void
    {
        if (!isset($_GET['page']) || $_GET['page'] !== PasswordScreen::PAGE_SLUG) {
            return;
        }

        // Reveal password (GET + nonce theo id)
        if (isset($_GET['action']) && $_GET['action'] === 'reveal' && isset($_GET['id'])) {
            $id = (int) $_GET['id'];
            check_admin_referer('reveal-password-' . $id);

            /** @var PasswordService $service */
            $service = Container::get(PasswordService::class);
            $plain   = $service->reveal_password($id);

            if ($plain === null) {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error"><p>' .
                        esc_html__('Không thể giải mã.', 'tmt-crm') . '</p></div>';
                });
            } else {
                add_action('admin_notices', function () use ($plain) {
                    echo '<div class="notice notice-success"><p>' .
                        esc_html__('Mật khẩu: ', 'tmt-crm') . '<code>' .
                        esc_html($plain) . '</code></p></div>';
                });
            }
        }
    }

    /**
     * Lưu password (create/update)
     * - Form cần:
     *   <input type="hidden" name="action" value="tmt_crm_password_save">
     *   wp_nonce_field('tmt_crm_password_save')
     *   (tùy ý) <input type="hidden" name="id" value="...">
     */
    public static function save(): void
    {
        check_admin_referer(self::NONCE_SAVE);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        $data = [
            'id'         => $id ?: null,
            'title'      => sanitize_text_field($_POST['title'] ?? ''),
            'username'   => isset($_POST['username']) ? sanitize_text_field($_POST['username']) : null,
            'password'   => isset($_POST['password']) ? (string) $_POST['password'] : null, // để service mã hoá
            'url'        => isset($_POST['url']) ? esc_url_raw($_POST['url']) : null,
            'notes'      => isset($_POST['notes']) ? wp_kses_post($_POST['notes']) : null,
            'owner_id'   => get_current_user_id(),
            'subject'     => isset($_POST['subject']) ? wp_kses_post($_POST['subject']) : null,
            'company_id' => isset($_POST['company_id']) ? (int) $_POST['company_id'] : null,
            'customer_id' => isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : null,
            'category'    => isset($_POST['category']) ? sanitize_text_field($_POST['category']) : null,
        ];

        $dto = PasswordItemDTO::from_array($data);

        /** @var PasswordService $service */
        $service = Container::get(PasswordService::class);

        if ($id > 0) {
            $ok = $service->update($id, $dto);
            self::redirect_back([
                'updated' => $ok ? '1' : '0',
                'act'     => 'update',
                'id'      => $id,
            ]);
        } else {
            $new_id = $service->create($dto);
            self::redirect_back([
                'updated' => $new_id > 0 ? '1' : '0',
                'act'     => 'create',
                'id'      => $new_id,
            ]);
        }
    }

    /**
     * Soft delete
     * - Form:
     *   <input type="hidden" name="action" value="tmt_crm_password_soft_delete">
     *   wp_nonce_field('tmt_crm_password_soft_delete')
     *   <input type="hidden" name="id" value="...">
     */
    public static function soft_delete(): void
    {
        check_admin_referer(self::NONCE_SOFT_DELETE);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            self::redirect_back(['deleted' => '0']);
        }

        /** @var PasswordService $service */
        $service = Container::get(PasswordService::class);
        $ok = $service->soft_delete($id);

        self::redirect_back([
            'deleted' => $ok ? '1' : '0',
            'id'      => $id,
        ]);
    }

    /**
     * Restore
     * - Form:
     *   <input type="hidden" name="action" value="tmt_crm_password_restore">
     *   wp_nonce_field('tmt_crm_password_restore')
     *   <input type="hidden" name="id" value="...">
     */
    public static function restore(): void
    {
        check_admin_referer(self::NONCE_RESTORE);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            self::redirect_back(['restored' => '0']);
        }

        /** @var PasswordService $service */
        $service = Container::get(PasswordService::class);
        $ok = $service->restore($id);

        self::redirect_back([
            'restored' => $ok ? '1' : '0',
            'id'       => $id,
        ]);
    }

    /** Chặn khách */
    public static function forbid(): void
    {
        wp_die(__('Bạn phải đăng nhập để thực hiện hành động này.', 'tmt-crm'));
    }

    /** Redirect về trang danh sách */
    private static function redirect_back(array $args = []): void
    {
        $url = add_query_arg(array_merge([
            'page' => PasswordScreen::PAGE_SLUG,
        ], $args), admin_url('admin.php'));

        wp_safe_redirect($url);
        exit;
    }
}
