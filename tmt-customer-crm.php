<?php

/**
 * Plugin Name: TMT Customer CRM
 * Description: CRM quản lý khách hàng cho WordPress/WooCommerce.
 * Version: 0.1.0
 * Author: TMT Việt Nam
 *
 * Ghi chú: Đây là bootstrap (file chính)
 */

if (!defined('ABSPATH')) exit;

// ------------------------------------------------------------
// 0) HẰNG SỐ & Autoload
// ------------------------------------------------------------
define('TMT_CRM_FILE', __FILE__);
define('TMT_CRM_PATH', plugin_dir_path(__FILE__));
define('TMT_CRM_URL',  plugin_dir_url(__FILE__));
define('TMT_CRM_DB_VERSION', '1.2.81'); // chỉ định 1 chỗ duy nhất

$composer_autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require $composer_autoload;
} elseif (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('[TMT CRM] vendor/autoload.php not found. Run "composer install".');
}

// ------------------------------------------------------------
// 1) Kiểm tra môi trường tối thiểu
// ------------------------------------------------------------
(function () {
    $ok = true;

    if (version_compare(PHP_VERSION, '8.1', '<')) {
        $ok = false;
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>TMT Customer CRM</strong> yêu cầu PHP >= 8.1.</p></div>';
        });
    }

    global $wp_version;
    if (isset($wp_version) && version_compare($wp_version, '6.0', '<')) {
        $ok = false;
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>TMT Customer CRM</strong> yêu cầu WordPress >= 6.0.</p></div>';
        });
    }

    if (!$ok) {
        return; // Ngăn plugin chạy tiếp nếu môi trường không đạt
    }
})();

// ------------------------------------------------------------
// 2) Use các thành phần Core/Modules
// ------------------------------------------------------------
use TMT\CRM\Shared\Infrastructure\Setup\Installer;
use TMT\CRM\Core\Records\CoreRecordsModule;
use TMT\CRM\Core\Files\FilesModule;
use TMT\CRM\Core\Settings\SettingsPage;
use TMT\CRM\Core\Numbering\NumberingModule;
use TMT\CRM\Core\Capabilities\CoreCapabilitiesModule;
use TMT\CRM\Core\Events\EventsModule;
use TMT\CRM\Core\Notifications\NotificationsModule;
use TMT\CRM\Core\Log\LogModule;
use TMT\CRM\Core\ExportImport\ExportImportModule;
use TMT\CRM\Core\Accounts\AccountsModule;


use TMT\CRM\Modules\Company\CompanyModule;
use TMT\CRM\Modules\Company\Menu as CompanyMenu;
use TMT\CRM\Modules\Customer\CustomerModule;
use TMT\CRM\Modules\Customer\Menu as CustomerMenu;
use TMT\CRM\Modules\Contact\ContactModule;
use TMT\CRM\Modules\Contact\Menu as ContactMenu;
use TMT\CRM\Modules\Quotation\QuotationModule;
use TMT\CRM\Modules\Quotation\Menu as QuotationMenu;
use TMT\CRM\Modules\Note\NoteModule;
use TMT\CRM\Modules\Note\Menu as NoteMenu;
use TMT\CRM\Modules\Password\PasswordModule;
use TMT\CRM\Modules\License\LicenseModule;

// Shared Menu (menu chung nếu có)
use TMT\CRM\Shared\Presentation\Menu as SharedMenu;

// Container
use TMT\CRM\Shared\Container\Container;

// ------------------------------------------------------------
// 3) Orchestrator (đã GOM toàn bộ hook từ Hooks.php vào 3 phase)
// ------------------------------------------------------------
final class TmtCrmPlugin
{
    public static function register_hooks(): void
    {
        // Phase 1: hạ tầng
        add_action('plugins_loaded', [self::class, 'boot_infrastructure'], 1);

        // Phase 2: core modules
        add_action('plugins_loaded', [self::class, 'boot_core_modules'], 2);

        // Phase 3: business/presentation
        add_action('plugins_loaded', [self::class, 'boot_business_and_presentation'], 3);
    }

    // --------------------------
    // PHASE 1 — INFRASTRUCTURE
    // --------------------------
    public static function boot_infrastructure(): void
    {
        // Installer tự quản lý activate + auto-upgrade (file chính)
        Installer::register();

        // 2) Boot Core/Events rất sớm (bind EventStore + EventBus, phát 'tmt_crm_events_ready')
        EventsModule::boot();

        // Core Records (soft delete, base repo …)
        CoreRecordsModule::register();

        // ==== Hooks.php → init (nền tảng, không phụ thuộc admin UI) ====
        add_action('init', [self::class, 'init_runtime']);

        // ==== Hooks.php → DI Container bindings (dùng chung sớm) ====
        add_action('plugins_loaded', [self::class, 'bind_container'], 1);
    }

    // ---------------------
    // PHASE 2 — CORE LAYER
    // ---------------------
    public static function boot_core_modules(): void
    {
        SettingsPage::register();
        FilesModule::bootstrap();
        NumberingModule::register();       // bootstrap (file chính)
        CoreCapabilitiesModule::register();
        NotificationsModule::boot();
        LogModule::bootstrap();
        AccountsModule::bootstrap(); // bootstrap (file chính)
        ExportImportModule::bootstrap();   // bootstrap (file chính)




        // ==== Hooks.php → AdminNoticeService::boot() (dùng toàn plugin) ====
        add_action('admin_init', function () {
            \TMT\CRM\Shared\Presentation\Support\AdminNoticeService::boot();
        }, 0);
    }

    // -------------------------------------
    // PHASE 3 — BUSINESS + PRESENTATION/UI
    // -------------------------------------
    public static function boot_business_and_presentation(): void
    {
        // ===== Business modules =====
        CompanyModule::register();
        CustomerModule::register();
        ContactModule::register();
        QuotationModule::register();
        NoteModule::register();
        PasswordModule::register();        // bootstrap (file chính)
        LicenseModule::register();         // bootstrap (file chính)


        // ===== Menus (module menus) =====
        CompanyMenu::register();
        CustomerMenu::register();
        ContactMenu::register();
        QuotationMenu::register();
        NoteMenu::register();
        // AccountsSettingsScreen::register_menu();
        // ===== Shared Menu (nếu có menu chung ở Shared\Presentation\Menu) =====
        add_action('admin_menu', [SharedMenu::class, 'register']);

        // ===== Assets cho admin =====
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin']);

        // ===== Select2 Assets + AJAX Controllers (từ Hooks.php) =====
        \TMT\CRM\Shared\Presentation\Assets\Select2Assets::bootstrap();
        \TMT\CRM\Modules\Customer\Presentation\Admin\Ajax\OwnerAjaxController::bootstrap();
        \TMT\CRM\Modules\Customer\Presentation\Admin\Ajax\CustomerAjaxController::bootstrap();

        // ===== Không chặn admin cho role CRM (từ Hooks.php) =====
        add_filter('woocommerce_prevent_admin_access', [self::class, 'allow_crm_roles_into_admin']);
    }

    // ==========================================================
    // =============== HANDLERS (thay cho Hooks.php) ============
    // ==========================================================

    // Phase 1: init — KHÔNG phụ thuộc admin UI
    public static function init_runtime(): void
    {
        // i18n
        load_plugin_textdomain(
            'tmt-customer-crm',
            false,
            dirname(plugin_basename(TMT_CRM_FILE)) . '/languages'
        );
        // Nếu có rewrite/schedule/default options → thêm tại đây
    }

    // Phase 1: DI Container bindings
    public static function bind_container(): void
    {
        // Chỉ alias/shortcut nhẹ, KHÔNG khởi tạo chuỗi phụ thuộc nặng ở đây.
        // Hạ tầng thật sẽ do từng ServiceProvider của module tự register.

        // Số ít alias chung có ích khi resolve bằng string (giữ backward-compat).
        Container::set(
            'event_bus',
            fn() =>
            Container::get(\TMT\CRM\Core\Events\Domain\Contracts\EventBusInterface::class)
        );

        // Alias thuận tiện nếu một số nơi gọi trực tiếp key kênh thông báo:
        Container::set('notifications.channels', function (): array {
            // Nếu NotificationsServiceProvider đã chạy, giá trị này sẽ được override.
            return apply_filters('tmt_crm_notifications_channels', []);
        });

        // (Optional) Đặt sẵn các khóa version/đường dẫn phục vụ view/service
        Container::set('tmt_crm.db_version', fn() => defined('TMT_CRM_DB_VERSION') ? TMT_CRM_DB_VERSION : '0.0.0');
        Container::set('tmt_crm.base_url', fn() => TMT_CRM_URL);
        Container::set('tmt_crm.base_path', fn() => TMT_CRM_PATH);
    }

    // Phase 3: Enqueue admin assets
    public static function enqueue_admin(): void
    {
        // Sử dụng DB version để cache-bust
        $ver = defined('TMT_CRM_DB_VERSION') ? TMT_CRM_DB_VERSION : '0.1.0';

        wp_enqueue_style('tmt-crm-admin', TMT_CRM_URL . 'assets/css/admin.css', [], $ver);
        wp_enqueue_script('tmt-crm-admin', TMT_CRM_URL . 'assets/js/admin.js', ['jquery'], $ver, true);

        // Nếu cần localize:
        // wp_localize_script('tmt-crm-admin', 'TMTCRM', ['ajax_url' => admin_url('admin-ajax.php')]);
    }

    // Phase 3: Cho phép role CRM vào /wp-admin
    public static function allow_crm_roles_into_admin($prevent_access)
    {
        if (!is_user_logged_in()) {
            return $prevent_access;
        }
        $u = wp_get_current_user();
        if (array_intersect(['tmt_crm_manager', 'tmt_crm_staff'], (array) $u->roles)) {
            return false;
        }
        return $prevent_access;
    }
}

// ------------------------------------------------------------
// 4) Kick off Orchestrator
// ------------------------------------------------------------
TmtCrmPlugin::register_hooks();

