<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\Core\Records\Infrastructure\Migration;

/**
 * Tạo 2 bảng: crm_audit_logs, crm_archives
 */
final class CoreAuditTablesMigrator
{
    public static function ensure_tables(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $t1 = $wpdb->prefix . 'crm_audit_logs';
        $t2 = $wpdb->prefix . 'crm_archives';

        $sql1 = "CREATE TABLE {$t1} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            entity VARCHAR(64) NOT NULL,
            entity_id BIGINT UNSIGNED NOT NULL,
            action VARCHAR(32) NOT NULL,
            actor_id BIGINT UNSIGNED NOT NULL,
            reason VARCHAR(255) NULL,
            diff_json LONGTEXT NULL,
            ip_address VARBINARY(16) NULL,
            user_agent VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            archive_id BIGINT UNSIGNED NULL,
            PRIMARY KEY  (id),
            KEY idx_entity (entity, entity_id),
            KEY idx_action_created (action, created_at),
            KEY idx_archive_id (archive_id)
        ) {$charset};";

        $sql2 = "CREATE TABLE {$t2} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            entity VARCHAR(64) NOT NULL,
            entity_id BIGINT UNSIGNED NOT NULL,
            snapshot_json LONGTEXT NOT NULL,
            relations_json LONGTEXT NULL,
            attachments_json LONGTEXT NULL,
            checksum_sha256 CHAR(64) NOT NULL,
            purged_by BIGINT UNSIGNED NOT NULL,
            purged_at DATETIME NOT NULL,
            purge_reason VARCHAR(255) NULL,
            PRIMARY KEY  (id),
            KEY idx_entity (entity, entity_id),
            KEY idx_purged_at (purged_at)
        ) {$charset};";

        dbDelta($sql1);
        dbDelta($sql2);
    }
}
