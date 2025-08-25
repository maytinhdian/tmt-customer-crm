<?php

namespace TMT\CRM\Shared;

use TMT\CRM\Presentation\Admin\Menu;
use TMT\CRM\Presentation\Admin\{CustomerScreen, CompanyScreen};
use TMT\CRM\Presentation\Admin\Company\Form\CompanyContactsBox;

use TMT\CRM\Infrastructure\Persistence\WpdbUserRepository;
use TMT\CRM\Infrastructure\Persistence\{
    WpdbCustomerRepository,
    WpdbCompanyRepository,
    WpdbCompanyContactRepository,
    WpdbEmploymentHistoryRepository
};

use TMT\CRM\Application\Services\{
    CustomerService,
    CompanyService,
    CompanyContactService,
    EmploymentHistoryService
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

        //Select2 AJAX Controller 
        \TMT\CRM\Presentation\Admin\Assets\Select2Assets::bootstrap();
        \TMT\CRM\Presentation\Admin\Ajax\CompanyAjaxController::bootstrap();
        \TMT\CRM\Presentation\Admin\Ajax\UserAjaxController::bootstrap();


        // Admin-post handlers (chạy khi submit form)
        add_action('admin_post_tmt_crm_company_add_contact',    [CompanyContactsBox::class, 'handle_add_contact']);
        add_action('admin_post_tmt_crm_company_end_contact',    [CompanyContactsBox::class, 'handle_end_contact']);
        add_action('admin_post_tmt_crm_company_set_primary',    [CompanyContactsBox::class, 'handle_set_primary']);
        add_action('admin_post_tmt_crm_company_delete_contact', [CompanyContactsBox::class, 'handle_delete_contact']);

        // Enqueue assets cho admin
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin']);
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
        Container::set('customer-repo',  fn() => new WpdbCustomerRepository($wpdb));
        Container::set('company-contact-repo',  fn() => new WpdbCompanyContactRepository($wpdb));
        Container::set('employment-history-repo',  fn() => new WpdbEmploymentHistoryRepository($wpdb));
        Container::set('user-repo',  fn() => new WpdbUserRepository($wpdb));

        // Services
        Container::set('company-service',   fn() => new CompanyService(
            Container::get('company-repo'),
            Container::get('company-contact-repo')
        ));
        Container::set('company-contact-service',  fn() => new CompanyContactService(Container::get('company-contact-repo')));
        Container::set('employment-history-service',  fn() => new EmploymentHistoryService(Container::get('employment-history-repo')));
        Container::set('customer-service',  fn() => new CustomerService(Container::get('customer-repo'), Container::get(('employment-history-repo'))));
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
