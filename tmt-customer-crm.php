<?php
/**
 * Plugin Name: TMT Customer CRM
 * Description: CRM quản lý khách hàng cho WordPress/WooCommerce.
 * Version: 0.1.0
 * Author: TMT Việt Nam
 */

if (!defined('ABSPATH')) { exit; }

require __DIR__ . '/vendor/autoload.php'; // nếu dùng composer

// Hằng số cơ bản
define('TMT_CRM_PATH', plugin_dir_path(__FILE__));
define('TMT_CRM_URL',  plugin_dir_url(__FILE__));

tmt_crm_bootstrap();

function tmt_crm_bootstrap(): void {
    // Đăng ký hooks chính
    TMT\CRM\Shared\Hooks::register();

    // Cài đặt DB khi activate
    register_activation_hook(__FILE__, function(){
        (new TMT\CRM\Infrastructure\Migrations\Installer())->run();
    });
}