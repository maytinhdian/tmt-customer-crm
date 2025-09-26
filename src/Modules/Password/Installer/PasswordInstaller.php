<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Password\Installer;

final class PasswordInstaller
{
    public static function maybe_install(): void
    {
        $option = 'tmt_crm_passwords_db';
        $current = get_option($option, '0');

        if (version_compare($current, '1.0.0', '<')) {
            self::install_1_0_0();
            update_option($option, '1.0.0');
        }
    }

    private static function install_1_0_0(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tmt_crm_passwords';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            username VARCHAR(190) NULL,
            ciphertext LONGTEXT NOT NULL,
            nonce VARBINARY(64) NOT NULL,
            url VARCHAR(255) NULL,
            notes TEXT NULL,
            owner_id BIGINT UNSIGNED NOT NULL,
            company_id BIGINT UNSIGNED NULL,
            customer_id BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            deleted_at DATETIME NULL,
            KEY idx_company (company_id),
            KEY idx_customer (customer_id),
            KEY idx_owner (owner_id),
            KEY idx_deleted (deleted_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
