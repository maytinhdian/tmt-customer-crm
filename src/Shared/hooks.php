<?php

namespace TMT\CRM\Shared;

use TMT\CRM\Presentation\Admin\Menu;
use TMT\CRM\Presentation\REST\Routes;
use TMT\CRM\Infrastructure\Integration\WooCommerceSync;
use TMT\CRM\Infrastructure\Persistence\{WpdbCompanyRepository, WpdbQuotationRepository, WpdbInvoiceRepository, WpdbDebtRepository, WpdbPaymentRepository};
use TMT\CRM\Application\Services\{CompanyService, QuotationService, InvoiceService, PaymentService};

final class Hooks
{
    public static function register(): void
    {
        add_action('init', [self::class, 'init']);
        add_action('admin_menu', [Menu::class, 'register']);
        add_action('rest_api_init', [Routes::class, 'register']);

        // Tích hợp Woo (nếu có)
        add_action('woocommerce_thankyou', [WooCommerceSync::class, 'sync_after_order']);

        // Enqueue assets admin
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin']);
    }

    public static function init(): void
    {
        // i18n
        load_plugin_textdomain('tmt-customer-crm', false, dirname(plugin_basename(TMT_CRM_PATH)) . '/languages');

                global $wpdb;
        // Repo
        Container::set('company-repo', fn() => new WpdbCompanyRepository($wpdb));
        Container::set('quotation-repo', fn() => new WpdbQuotationRepository($wpdb));
        Container::set('invoice-repo', fn() => new WpdbInvoiceRepository($wpdb));
        Container::set('debt-repo', fn() => new WpdbDebtRepository($wpdb));
        Container::set('payment-repo', fn() => new WpdbPaymentRepository($wpdb));

        // Services
        Container::set('company-service', fn() => new CompanyService(Container::get('company-repo')));
        Container::set('quotation-service', fn() => new QuotationService(Container::get('quotation-repo')));
        Container::set('invoice-service', fn() => new InvoiceService(Container::get('invoice-repo')));
        Container::set('payment-service', fn() => new PaymentService(Container::get('payment-repo'), Container::get('invoice-repo')));
    }

    public static function enqueue_admin(): void
    {
        wp_enqueue_style('tmt-crm-admin', TMT_CRM_URL . 'assets/css/admin.css', [], '0.1.0');
        wp_enqueue_script('tmt-crm-admin', TMT_CRM_URL . 'assets/js/admin.js', ['jquery'], '0.1.0', true);
    }
}
