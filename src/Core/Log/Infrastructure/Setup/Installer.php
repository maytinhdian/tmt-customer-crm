<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Log\Infrastructure\Setup;

use TMT\CRM\Core\Log\LogModule;

final class Installer
{
    public static function maybe_install(): void
    {
        $installed = get_option(LogModule::OPTION_VERSION);
        if ($installed === LogModule::VERSION) { return; }

        global $wpdb;
        $table = $wpdb->prefix . 'tmt_crm_logs';
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            channel VARCHAR(50) NOT NULL,
            level ENUM('debug','info','warning','error','critical') NOT NULL,
            message TEXT NOT NULL,
            context LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            ip VARCHAR(45) NULL,
            module VARCHAR(100) NULL,
            request_id VARCHAR(64) NULL,
            PRIMARY KEY (id),
            KEY idx_created_at (created_at),
            KEY idx_level (level),
            KEY idx_channel (channel)
        ) {$charset_collate};";

        dbDelta($sql);
        update_option(LogModule::OPTION_VERSION, LogModule::VERSION);
    }
}
