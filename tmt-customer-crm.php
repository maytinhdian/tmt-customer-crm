<?php

/**
 * Plugin Name: TMT Customer CRM
 * Description: CRM quản lý khách hàng cho WordPress/WooCommerce.
 * Version: 0.1.0
 * Author: TMT Việt Nam
 */

if (!defined('ABSPATH')) {
    exit;
}

use TMT\CRM\Infrastructure\Security\CustomerRoleService;

/** ====== 0) Autoload an toàn ====== */
$composer_autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require $composer_autoload;
} else {
    // Không bắt buộc, nhưng cảnh báo dev trong môi trường local
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[TMT CRM] vendor/autoload.php not found. Run "composer install".');
    }
}

/** ====== 1) Hằng số cơ bản ====== */
define('TMT_CRM_FILE', __FILE__);
define('TMT_CRM_PATH', plugin_dir_path(__FILE__));
define('TMT_CRM_URL',  plugin_dir_url(__FILE__));
define('TMT_CRM_DB_VERSION', '1.0.0'); // tăng số này khi đổi schema

/** ====== 2) Đăng ký activation: tạo bảng + lưu version ====== */
register_activation_hook(TMT_CRM_FILE, function () {
    if (class_exists(\TMT\CRM\Infrastructure\Migrations\Installer::class)) {
        (new \TMT\CRM\Infrastructure\Migrations\Installer())->run();
    }
    update_option('tmt_crm_db_version', TMT_CRM_DB_VERSION);

    // Cài quyền + roles
    CustomerRoleService::install();
});

/** ====== 3) Auto-upgrade DB khi plugin load ====== */
add_action('plugins_loaded', function () {
    $installed_ver = get_option('tmt_crm_db_version');
    if ($installed_ver !== TMT_CRM_DB_VERSION) {
        if (class_exists(\TMT\CRM\Infrastructure\Migrations\Installer::class)) {
            (new \TMT\CRM\Infrastructure\Migrations\Installer())->run();
        }
        update_option('tmt_crm_db_version', TMT_CRM_DB_VERSION);
    }

    // Khởi động hệ thống sau khi đảm bảo schema OK
    if (class_exists(\TMT\CRM\Shared\Hooks::class)) {
        \TMT\CRM\Shared\Hooks::register();

        // Nếu bạn đang chạy chế độ "Customer-only", nhớ bật boot screen:
        // \TMT\CRM\Presentation\Admin\CustomerScreen::boot();
    }
});

// 1) Không chặn admin cho role CRM
add_filter('woocommerce_prevent_admin_access', function ($prevent_access) {
    if (!is_user_logged_in()) return $prevent_access;
    $u = wp_get_current_user();
    if (array_intersect(['tmt_crm_manager', 'tmt_crm_staff'], (array)$u->roles)) {
        return false; // cho phép vào wp-admin
    }
    return $prevent_access;
});
