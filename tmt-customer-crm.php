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

use \TMT\CRM\Shared\Infrastructure\Setup\Installer;

define('TMT_CRM_FILE', __FILE__);
define('TMT_CRM_PATH', plugin_dir_path(__FILE__));
define('TMT_CRM_URL',  plugin_dir_url(__FILE__));
define('TMT_CRM_DB_VERSION', '1.2.81'); // chỉ định 1 chỗ duy nhất

// 0) Autoload
$composer_autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require $composer_autoload;
} elseif (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('[TMT CRM] vendor/autoload.php not found. Run "composer install".');
}

// 1) Activation: chạy migrate + set version + roles
// register_activation_hook(TMT_CRM_FILE, function () {
//     /** @var \wpdb $wpdb */
//     global $wpdb;

//     if (class_exists(Installer::class)) {
//         Installer::run_if_needed($wpdb, TMT_CRM_DB_VERSION);
//     }
//     update_option('tmt_crm_db_version', TMT_CRM_DB_VERSION, true);
// });

add_action('plugins_loaded', function () {
    Installer::register(); // (file chính)
    // ... gọi Module::register() như hiện tại
}, 1);

// 2) Auto-upgrade khi plugin load (so sánh version & migrate)
add_action('plugins_loaded', function () {
    // /** @var \wpdb $wpdb */
    // global $wpdb;

    // $installed_ver = (string) get_option('tmt_crm_db_version', '');

    // if ($installed_ver !== TMT_CRM_DB_VERSION && class_exists(Installer::class)) {
    //     Installer::run_if_needed($wpdb, TMT_CRM_DB_VERSION);
    //     update_option('tmt_crm_db_version', TMT_CRM_DB_VERSION, true);
    // }

    // bật Role packs + map_meta_cap (own/any, DIP)
    // SecurityBootstrap::init();

    // Khởi động hệ thống sau khi schema OK
    if (class_exists(\TMT\CRM\Shared\Hooks::class)) {
        \TMT\CRM\Shared\Hooks::register();
    }
});

use TMT\CRM\Modules\Customer\Menu as CustomerMenu;
use TMT\CRM\Modules\Customer\CustomerModule as CustomerModule;

add_action('plugins_loaded', function () {
    CustomerModule::register();
    CustomerMenu::register(); // mỗi module tự có Menu::register()

}, 1);

use TMT\CRM\Modules\Quotation\Menu as QuotationMenu;
use TMT\CRM\Modules\Quotation\QuotationModule as QuotationModule;

add_action('plugins_loaded', function () {
    QuotationModule::register();
    QuotationMenu::register(); // mỗi module tự có Menu::register()

}, 1);

use TMT\CRM\Modules\Company\Menu as CompanyMenu;
use TMT\CRM\Modules\Company\CompanyModule as CompanyModule;

add_action('plugins_loaded', function () {
    CompanyMenu::register();
    CompanyModule::register(); // mỗi module tự có Menu::register()

}, 1);

use TMT\CRM\Modules\Contact\Menu as ContactMenu;
use TMT\CRM\Modules\Contact\ContactModule as ContactModule;

add_action('plugins_loaded', function () {
    ContactMenu::register();
    ContactModule::register(); // mỗi module tự có Menu::register()

}, 1);


use TMT\CRM\Modules\Note\Menu as NotesMenu;
use TMT\CRM\Modules\Note\NoteModule as NoteModule;

add_action('plugins_loaded', function () {
    NotesMenu::register();
    NoteModule::register(); // mỗi module tự có Menu::register()

}, 1);

use TMT\CRM\Core\Settings\SettingsPage;
use TMT\CRM\Core\Records\CoreRecordsModule;
use TMT\CRM\Core\Capabilities\CoreCapabilitiesModule;
use TMT\CRM\Core\Notifications\NotificationsModule;

add_action('plugins_loaded', function () {
    CoreRecordsModule::register(); // bootstrap (file chính)
}, 1);
// Bridge WP action -> EventBus (đăng ký càng sớm càng tốt)
add_action('plugins_loaded', static function () {
    \TMT\CRM\Shared\EventBus\EventBusBridge::register();
}, 0);

add_action('plugins_loaded', function () {
    \TMT\CRM\Shared\Infrastructure\Setup\Installer::register(); // (file chính)
    SettingsPage::register();
    \TMT\CRM\Core\Numbering\NumberingModule::register(); // bootstrap (file chính)
    CoreCapabilitiesModule::register();

    NotificationsModule::register();
}, 1);

use TMT\CRM\Modules\Password\PasswordModule;

add_action('plugins_loaded', function () {
    PasswordModule::register(); // bootstrap (file chính)
}, 1);

use TMT\CRM\Modules\License\LicenseModule;
use TMT\CRM\Core\Events\EventsModule;

add_action('plugins_loaded', function () {
    // 1) Boot Core/Events trước (đăng EventBus)
    EventsModule::bootstrap(); // (file chính)

    LicenseModule::register(); // bootstrap (file chính)
    \TMT\CRM\Core\Log\LogModule::register();
}, 1);

use TMT\CRM\Core\ExportImport\ExportImportModule;

ExportImportModule::bootstrap();
