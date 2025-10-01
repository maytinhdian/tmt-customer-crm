<?php
declare(strict_types=1);

namespace TMT\CRM\Core\ExportImport\Infrastructure\Setup;

use TMT\CRM\Shared\Infrastructure\Setup\Migration\BaseMigrator;

final class Migrator extends BaseMigrator
{
    public static function module_key(): string { return 'export_import'; }
    public static function target_version(): string { return '0.1.0'; }

    public function install(): void
    {
        $db = $this->db; $charset = $this->charset_collate;

        $table1 = $db->prefix . 'tmt_crm_export_jobs';
        $sql1 = "CREATE TABLE {$table1} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            entity_type VARCHAR(50) NOT NULL,
            filters LONGTEXT NULL,
            columns LONGTEXT NULL,
            format VARCHAR(10) NOT NULL DEFAULT 'csv',
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            file_path TEXT NULL,
            error_message TEXT NULL,
            created_by BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            finished_at DATETIME NULL
        ) {$charset};";

        $table2 = $db->prefix . 'tmt_crm_import_jobs';
        $sql2 = "CREATE TABLE {$table2} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            entity_type VARCHAR(50) NOT NULL,
            source_file TEXT NOT NULL,
            format VARCHAR(10) NOT NULL DEFAULT 'csv',
            mapping LONGTEXT NULL,
            has_header TINYINT(1) NOT NULL DEFAULT 1,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            error_message TEXT NULL,
            total_rows INT UNSIGNED NOT NULL DEFAULT 0,
            success_rows INT UNSIGNED NOT NULL DEFAULT 0,
            error_rows INT UNSIGNED NOT NULL DEFAULT 0,
            created_by BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            finished_at DATETIME NULL
        ) {$charset};";

        $table3 = $db->prefix . 'tmt_crm_mapping_rules';
        $sql3 = "CREATE TABLE {$table3} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            entity_type VARCHAR(50) NOT NULL,
            profile_name VARCHAR(100) NOT NULL,
            mapping LONGTEXT NOT NULL,
            owner_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
    }

    public function upgrade(string $from_version, string $to_version): void
    {
        // Chưa có nâng cấp ở MVP
    }
}
