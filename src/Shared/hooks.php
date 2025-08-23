<?php

namespace TMT\CRM\Shared;

use TMT\CRM\Presentation\Admin\Menu;
use TMT\CRM\Presentation\Admin\{CustomerScreen,CompanyScreen};



use TMT\CRM\Infrastructure\Persistence\{

    WpdbCustomerRepository,
    WpdbCompanyRepository
};

use TMT\CRM\Application\Services\{

    CustomerService,
    CompanyService
};

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


        add_action('admin_init', [CustomerScreen::class, 'boot']);
        add_action('admin_init', [CompanyScreen::class, 'boot']);

        // Enqueue assets cho admin
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin']);

        // Đăng ký CustomerScreen (menu con, handlers admin_post...)
        // CustomerScreen::boot();
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

        // Repositories
        Container::set('company-repo',   fn() => new WpdbCompanyRepository($wpdb));
        Container::set('customer-repo',  fn() => new WpdbCustomerRepository($wpdb)); // ← NEW

        // Services
        Container::set('company-service',   fn() => new CompanyService(Container::get('company-repo')));
        Container::set('customer-service',  fn() => new CustomerService(Container::get('customer-repo'))); // ← NEW
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
