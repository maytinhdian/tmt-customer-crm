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

use \TMT\CRM\Infrastructure\Migrations\Installer;
use TMT\CRM\Infrastructure\Security\SecurityBootstrap;

define('TMT_CRM_FILE', __FILE__);
define('TMT_CRM_PATH', plugin_dir_path(__FILE__));
define('TMT_CRM_URL',  plugin_dir_url(__FILE__));
define('TMT_CRM_DB_VERSION', '1.2.5'); // chỉ định 1 chỗ duy nhất

// 0) Autoload
$composer_autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require $composer_autoload;
} elseif (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('[TMT CRM] vendor/autoload.php not found. Run "composer install".');
}

// 1) Activation: chạy migrate + set version + roles
register_activation_hook(TMT_CRM_FILE, function () {
    /** @var \wpdb $wpdb */
    global $wpdb;

    if (class_exists(Installer::class)) {
        Installer::run_if_needed($wpdb, TMT_CRM_DB_VERSION);
    }
    update_option('tmt_crm_db_version', TMT_CRM_DB_VERSION, true);
});

// 2) Auto-upgrade khi plugin load (so sánh version & migrate)
add_action('plugins_loaded', function () {
    /** @var \wpdb $wpdb */
    global $wpdb;

    $installed_ver = (string) get_option('tmt_crm_db_version', '');

    if ($installed_ver !== TMT_CRM_DB_VERSION && class_exists(Installer::class)) {
        Installer::run_if_needed($wpdb, TMT_CRM_DB_VERSION);
        update_option('tmt_crm_db_version', TMT_CRM_DB_VERSION, true);
    }

    // bật Role packs + map_meta_cap (own/any, DIP)
    SecurityBootstrap::init();

    // Khởi động hệ thống sau khi schema OK
    if (class_exists(\TMT\CRM\Shared\Hooks::class)) {
        \TMT\CRM\Shared\Hooks::register();
    }
});




