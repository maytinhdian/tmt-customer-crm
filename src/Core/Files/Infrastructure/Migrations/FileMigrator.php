<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Files\Infrastructure\Migrations;

use TMT\CRM\Core\Files\FilesModule;

/**
 * FileMigrator
 * - Tạo bảng ánh xạ file ↔ entity
 * - Quản lý nâng cấp schema theo version
 */
final class FileMigrator
{
    public static function maybe_install(): void
    {
        $installed = get_option(FilesModule::OPTION_VERSION);

        if (!$installed) {
            self::install();
            update_option(FilesModule::OPTION_VERSION, FilesModule::VERSION);
            return;
        }

        if (version_compare((string)$installed, FilesModule::VERSION, '<')) {
            self::upgrade((string)$installed, FilesModule::VERSION);
            update_option(FilesModule::OPTION_VERSION, FilesModule::VERSION);
        }
    }

    private static function install(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tmt_crm_files';
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "
        CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            entity_type VARCHAR(50) NOT NULL,
            entity_id BIGINT UNSIGNED NOT NULL,
            attachment_id BIGINT UNSIGNED NOT NULL,
            uploaded_by BIGINT UNSIGNED NOT NULL,
            uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_entity (entity_type, entity_id),
            KEY idx_attachment (attachment_id),
            UNIQUE KEY uniq_map (entity_type, entity_id, attachment_id)
        ) {$charset_collate};
        ";

        dbDelta($sql);
    }

    private static function upgrade(string $from, string $to): void
    {
        // Ví dụ nâng cấp:
        // if (version_compare($from, '1.0.1', '<')) { self::migrate_101(); }
    }

    private static function migrate_101(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tmt_crm_files';
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "
        CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            entity_type VARCHAR(50) NOT NULL,
            entity_id BIGINT UNSIGNED NOT NULL,
            attachment_id BIGINT UNSIGNED NOT NULL,
            uploaded_by BIGINT UNSIGNED NOT NULL,
            uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL DEFAULT NULL,
            deleted_by BIGINT UNSIGNED NULL DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_entity (entity_type, entity_id),
            KEY idx_attachment (attachment_id),
            KEY idx_deleted (deleted_at),
            UNIQUE KEY uniq_map (entity_type, entity_id, attachment_id)
        ) {$charset_collate};
        ";

        dbDelta($sql);
    }
}
