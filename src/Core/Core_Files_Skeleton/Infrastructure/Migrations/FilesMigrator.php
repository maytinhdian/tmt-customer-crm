<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Files\Infrastructure\Migrations;

use TMT\CRM\Core\Files\FilesModule;

final class FilesMigrator
{
    public static function maybe_install(): void
    {
        $installed = get_option(FilesModule::OPTION_VERSION);
        if ($installed === FilesModule::VERSION) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'tmt_crm_files';
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            owner_id BIGINT UNSIGNED NOT NULL,
            subject_type VARCHAR(50) NOT NULL,
            subject_id BIGINT UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            mime_type VARCHAR(150) NOT NULL,
            extension VARCHAR(20) NULL,
            size BIGINT UNSIGNED NOT NULL,
            storage VARCHAR(30) NOT NULL DEFAULT 'wp_uploads',
            storage_path TEXT NOT NULL,
            public_url TEXT NULL,
            checksum_sha256 CHAR(64) NULL,
            status ENUM('active','inactive') DEFAULT 'active',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL,
            deleted_by BIGINT NULL,
            delete_reason VARCHAR(255) NULL,
            INDEX(subject_type, subject_id),
            INDEX(owner_id),
            INDEX(deleted_at)
        ) {$charset_collate};";

        dbDelta($sql);

        update_option(FilesModule::OPTION_VERSION, FilesModule::VERSION);
    }
}
