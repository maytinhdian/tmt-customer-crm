<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Numbering\Infrastructure;

use TMT\CRM\Core\Numbering\NumberingModule;

/**
 * Tạo bảng và cập nhật phiên bản module
 */
final class Installer
{
    public static function maybe_install(): void
    {
        $installed = get_option(NumberingModule::OPTION_VERSION);
        if ($installed === NumberingModule::VERSION) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'crm_numbering_rules';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            entity_type VARCHAR(50) NOT NULL UNIQUE,
            prefix VARCHAR(50) DEFAULT '',
            suffix VARCHAR(50) DEFAULT '',
            padding TINYINT UNSIGNED DEFAULT 4,
            reset ENUM('never','yearly','monthly') DEFAULT 'never',
            last_number INT UNSIGNED DEFAULT 0,
            year_key INT UNSIGNED DEFAULT 0,
            month_key TINYINT UNSIGNED DEFAULT 0,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset_collate};";

        dbDelta($sql);

        update_option(NumberingModule::OPTION_VERSION, NumberingModule::VERSION);
    }
}
