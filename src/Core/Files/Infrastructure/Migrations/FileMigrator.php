<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Files\Infrastructure\Migrations;

use TMT\CRM\Shared\Infrastructure\Setup\Migration\BaseMigrator;

final class FileMigrator extends BaseMigrator
{
    public static function module_key(): string
    {
        return 'core_files';
    }
    public static function target_version(): string
    {
        return '1.0.0';
    }

    public function install(): void
    {
        $table = $this->db->prefix . 'tmt_crm_files';
        $charset = $this->charset_collate;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            entity_type VARCHAR(100) NOT NULL,
            entity_id BIGINT UNSIGNED NOT NULL,
            storage VARCHAR(50) NOT NULL,
            path TEXT NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            mime VARCHAR(150) NOT NULL,
            size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
            checksum VARCHAR(64) DEFAULT NULL,
            version INT UNSIGNED NOT NULL DEFAULT 1,
            visibility ENUM('private','public') NOT NULL DEFAULT 'private',
            uploaded_by BIGINT UNSIGNED NOT NULL,
            uploaded_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            deleted_at DATETIME DEFAULT NULL,
            meta LONGTEXT DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_entity (entity_type, entity_id),
            KEY idx_deleted (deleted_at)
        ) {$charset};";

        dbDelta($sql);
    }

    public function upgrade(string $from_version): void
    {
        if ($from_version === '') {
            $this->install();
            return;
        }
    }
}
