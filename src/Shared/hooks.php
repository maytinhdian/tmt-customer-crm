<?php

namespace TMT\CRM\Shared;

use TMT\CRM\Presentation\Admin\Menu;
use TMT\CRM\Modules\Customer\Infrastructure\Persistence\WpdbUserRepository;
use TMT\CRM\Presentation\Admin\Support\AdminNoticeService;
use TMT\CRM\Shared\Container\Container;


final class Hooks
{
    public static function register(): void
    {
        // Core WP / REST / Admin
        add_action('init', [self::class, 'init']);
        add_action('admin_menu', [Menu::class, 'register']);
        // add_action('rest_api_init', [Routes::class, 'register']);

        // WooCommerce integration (nếu có)
        // add_action('woocommerce_thankyou', [WooCommerceSync::class, 'sync_after_order']);

        // error_log('[XDEBUG TEST] __FILE__=' . __FILE__);

        //Notice Services
        add_action('admin_init', function () {
            // error_log('[TMT Hooks] AdminNoticeService::boot() is running...');
            AdminNoticeService::boot();
        }, 0);


        //Select2 AJAX Controller 
        \TMT\CRM\Presentation\Admin\Assets\Select2Assets::bootstrap();
        \TMT\CRM\Presentation\Admin\Ajax\OwnerAjaxController::bootstrap();
        \TMT\CRM\Presentation\Admin\Ajax\CustomerAjaxController::bootstrap();

        // Enqueue assets cho admin
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin']);

        // Không chặn admin cho role CRM
        add_filter('woocommerce_prevent_admin_access', function ($prevent_access) {
            if (!is_user_logged_in()) return $prevent_access;
            $u = wp_get_current_user();
            if (array_intersect(['tmt_crm_manager', 'tmt_crm_staff'], (array) $u->roles)) {
                return false;
            }
            return $prevent_access;
        });
    }

    public static function init(): void
    {
        /**
         * i18n: nếu bạn có hằng số TMT_CRM_FILE (đường dẫn file plugin chính),
         * nên dùng plugin_basename(TMT_CRM_FILE). Ở đây vẫn giữ nguyên để không phá vỡ cấu trúc của bạn.
         */
        load_plugin_textdomain(
            'tmt-customer-crm',
            false,
            dirname(plugin_basename(TMT_CRM_PATH)) . '/languages'
        );

        // Đăng ký DI vào Container
        global $wpdb;
        //---------------------
        // Bind theo Interface
        //---------------------

        Container::set('user-repo',  fn() => new WpdbUserRepository($wpdb));
    }

    public static function enqueue_admin(): void
    {
        // Nếu bạn có hằng số phiên bản plugin, ví dụ TMT_CRM_VER, dùng nó để cache-bust:
        // $ver = defined('TMT_CRM_VER') ? TMT_CRM_VER : '0.1.0';
        $ver = '0.1.0';

        wp_enqueue_style('tmt-crm-admin', TMT_CRM_URL . 'assets/css/admin.css', [], $ver);
        wp_enqueue_script('tmt-crm-admin', TMT_CRM_URL . 'assets/js/admin.js', ['jquery'], $ver, true);
    }
}
