<?php

namespace TMT\CRM\Shared;

use TMT\CRM\Presentation\Admin\Menu;
use TMT\CRM\Presentation\Admin\CustomerScreen;
// use TMT\CRM\Presentation\REST\Routes;
// use TMT\CRM\Infrastructure\Integration\WooCommerceSync;


//====== Persistence: chỉ giữ Customer Repo ======
/*** WpdbCompanyRepository,
    WpdbQuotationRepository,
    WpdbInvoiceRepository,
    WpdbDebtRepository,
    WpdbPaymentRepository,
 */


// ====== Application Services: chỉ giữ Customer Service ======
/****
 *     CompanyService,
    QuotationService,
    InvoiceService,
    PaymentService,
 */

use TMT\CRM\Infrastructure\Persistence\{

    WpdbCustomerRepository
};

use TMT\CRM\Application\Services\{

    CustomerService
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

        // Enqueue assets cho admin
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin']);

        // Đăng ký CustomerScreen (menu con, handlers admin_post...)
        CustomerScreen::boot();
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
        // Container::set('company-repo',   fn() => new WpdbCompanyRepository($wpdb));
        // Container::set('quotation-repo', fn() => new WpdbQuotationRepository($wpdb));
        // Container::set('invoice-repo',   fn() => new WpdbInvoiceRepository($wpdb));
        // Container::set('debt-repo',      fn() => new WpdbDebtRepository($wpdb));
        // Container::set('payment-repo',   fn() => new WpdbPaymentRepository($wpdb));
        Container::set('customer-repo',  fn() => new WpdbCustomerRepository($wpdb)); // ← NEW

        // Services
        // Container::set('company-service',   fn() => new CompanyService(Container::get('company-repo')));
        // Container::set('quotation-service', fn() => new QuotationService(Container::get('quotation-repo')));
        // Container::set('invoice-service',   fn() => new InvoiceService(Container::get('invoice-repo')));
        // Container::set('payment-service',   fn() => new PaymentService(Container::get('payment-repo'), Container::get('invoice-repo')));
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
