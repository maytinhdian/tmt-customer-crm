<?php

namespace TMT\CRM\Shared;

use TMT\CRM\Presentation\Admin\Menu;
use TMT\CRM\Infrastructure\Users\WpdbUserRepository;
use TMT\CRM\Presentation\Admin\Support\AdminNoticeService;
use TMT\CRM\Application\Validation\CompanyContactValidator;
use TMT\CRM\Presentation\Admin\Screen\{CustomerScreen, CompanyScreen, QuoteScreen, CompanyContactsScreen};
use TMT\CRM\Presentation\Admin\Controller\CompanyContactController;
use TMT\CRM\Domain\Repositories\{
    CompanyRepositoryInterface,
    FileRepositoryInterface,
    NoteRepositoryInterface,
    CustomerRepositoryInterface,
    CompanyContactRepositoryInterface
};

use TMT\CRM\Infrastructure\Persistence\{
    WpdbCustomerRepository,
    WpdbCompanyRepository,
    WpdbCompanyContactRepository,
    WpdbEmploymentHistoryRepository,
    WpdbSequenceRepository,
    WpdbQuoteRepository,
    WpdbFileRepository,
    WpdbNoteRepository,
    WpdbQuoteQueryRepository
};
use TMT\CRM\Application\Services\{
    CustomerService,
    CompanyService,
    CompanyContactService,
    EmploymentHistoryService,
    FileService,
    NoteService,
    NumberingService,
    QuoteService,
    CompanyContactQueryService
};
use TMT\CRM\Presentation\Admin\Controller\NotesFilesController;

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


        add_action('admin_init', [CustomerScreen::class, 'boot']);
        add_action('admin_init', [CompanyScreen::class, 'boot']);
        add_action('admin_init', [QuoteScreen::class, 'boot']);
        add_action('admin_init', [CompanyContactsScreen::class, 'boot']);

        //Controller 
        add_action('admin_init', function () {
            // error_log('[CRM Hooks] CompanyContactController::register() is ready...');
            CompanyContactController::register();
        });

        //Notice Services
        add_action('admin_init', function () {
            // error_log('[TMT Hooks] AdminNoticeService::boot() is running...');
            AdminNoticeService::boot();
        }, 0);



        //Select2 AJAX Controller 
        \TMT\CRM\Presentation\Admin\Assets\Select2Assets::bootstrap();
        \TMT\CRM\Presentation\Admin\Ajax\CompanyAjaxController::bootstrap();
        \TMT\CRM\Presentation\Admin\Ajax\OwnerAjaxController::bootstrap();
        \TMT\CRM\Presentation\Admin\Ajax\CustomerAjaxController::bootstrap();


        // // Admin-post handlers (chạy khi submit form)
        // add_action('admin_post_tmt_crm_company_add_contact',    [CompanyContactsBox::class, 'handle_add_contact']);
        // add_action('admin_post_tmt_crm_company_end_contact',    [CompanyContactsBox::class, 'handle_end_contact']);
        // add_action('admin_post_tmt_crm_company_set_primary',    [CompanyContactsBox::class, 'handle_set_primary']);
        // add_action('admin_post_tmt_crm_company_delete_contact', [CompanyContactsBox::class, 'handle_delete_contact']);

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
        Container::set(CustomerRepositoryInterface::class,       fn() => new WpdbCustomerRepository($wpdb));
        Container::set(NoteRepositoryInterface::class,            fn() => new WpdbNoteRepository($wpdb));
        Container::set(FileRepositoryInterface::class,            fn() => new WpdbFileRepository($wpdb));


        // -------------------------
        // Repositories (aliases)
        // -------------------------
        Container::set('company-repo',   fn() => new WpdbCompanyRepository($wpdb));
        Container::set('customer-repo',  fn() => Container::get(CustomerRepositoryInterface::class));
        Container::set('company-contact-repo',  fn() => new WpdbCompanyContactRepository($wpdb));
        Container::set('employment-history-repo',  fn() => new WpdbEmploymentHistoryRepository($wpdb));
        Container::set('user-repo',  fn() => new WpdbUserRepository($wpdb));
        Container::set('quote-query-repo', fn() => new WpdbQuoteQueryRepository($wpdb));
        Container::set('sequence-repo', fn() => new WpdbSequenceRepository($wpdb));
        Container::set('quote-repo', fn() => new WpdbQuoteRepository($wpdb));
        Container::set('note-repo',            fn() => new WpdbNoteRepository($wpdb));
        Container::set('file-repo',            fn() => new WpdbFileRepository($wpdb));

        // ------------------------
        // Validator (đăng ký để tái sử dụng ở nhiều service/controller)
        // -------------------------
        Container::set('company-contact-validator', fn() => new CompanyContactValidator(
            Container::get('company-contact-repo')
        ));


        // Services
        Container::set('numbering', fn() => new NumberingService(Container::get('sequence-repo')));
        Container::set('note-service', fn() => new NoteService(Container::get('note-repo')));
        Container::set('file-service', fn() => new FileService(Container::get('file-repo')));
        Container::set('quote-service', fn() => new QuoteService(Container::get('quote-repo'), Container::get('numbering')));
        Container::set('company-service',   fn() => new CompanyService(Container::get('company-repo'), Container::get('company-contact-repo')));
        Container::set('company-contact-service',  fn() => new CompanyContactService(Container::get('company-contact-repo'), Container::get('customer-repo'), Container::get('company-repo'), Container::get('company-contact-validator')));
        Container::set('employment-history-service',  fn() => new EmploymentHistoryService(Container::get('employment-history-repo')));
        Container::set('customer-service',  fn() => new CustomerService(Container::get('customer-repo'), Container::get(('employment-history-repo'))));
        Container::set('company-contact-query-service',  fn() => new CompanyContactQueryService(Container::get('company-contact-repo'), Container::get('customer-repo'), Container::get('user-repo'), Container::get('company-repo')));
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
