<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Infrastructure\Migrations;

use TMT\CRM\Shared\Infrastructure\Setup\Migration\BaseMigrator;

/**
 * Core/Notifications Migrator
 * - Tạo 4 bảng: notifications, notification_deliveries, notification_templates, notification_preferences
 * - Theo convention: dùng $wpdb->prefix cho tên bảng; CHARSET COLLATE từ $this->charset_collate
 * - Chỉ dùng ALTER/CREATE an toàn, tránh phá dữ liệu nếu đã có.
 */
final class NotificationsMigrator extends BaseMigrator
{
    public static function module_key(): string
    {
        return 'notifications';
    }

    public static function target_version(): string
    {
        return '1.0.0';
    }

    public function install(): void
    {

        $this->create_tables_v1();

        // (chỗ này để dành cho các phiên bản >= 1.0.x nếu cần ALTER thêm)
        // if (version_compare($from_version, '1.0.1', '<')) { ... }

        $this->set_version(self::target_version());
    }

    private function create_tables_v1(): void
    {
        $prefix = $this->db->prefix;
        $charset = $this->charset_collate;

        $tbl_notifications          = $prefix . 'tmt_crm_notifications';
        $tbl_deliveries             = $prefix . 'tmt_crm_notification_deliveries';
        $tbl_templates              = $prefix . 'tmt_crm_notification_templates';
        $tbl_preferences            = $prefix . 'tmt_crm_notification_preferences';

        // ======= notifications =======
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$tbl_notifications}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `event_key` VARCHAR(64) NOT NULL,
            `entity_type` VARCHAR(50) NOT NULL,
            `entity_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
            `template_key` VARCHAR(64) NULL,
            `summary` VARCHAR(255) NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` BIGINT UNSIGNED NULL,
            PRIMARY KEY (`id`),
            KEY `idx_event` (`event_key`),
            KEY `idx_entity` (`entity_type`, `entity_id`),
            KEY `idx_created_at` (`created_at`)
        ) {$charset};");

        // ======= deliveries =======
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$tbl_deliveries}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `notification_id` BIGINT UNSIGNED NOT NULL,
            `channel` VARCHAR(32) NOT NULL,              
            `recipient_type` VARCHAR(16) NOT NULL,       
            `recipient_value` VARCHAR(191) NOT NULL,    
            `status` ENUM('queued','sent','failed','read') NOT NULL DEFAULT 'queued',
            `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
            `last_error` TEXT NULL,
            `sent_at` DATETIME NULL,
            `read_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_notification` (`notification_id`),
            KEY `idx_status` (`status`),
            KEY `idx_channel` (`channel`),
            KEY `idx_recipient` (`recipient_type`,`recipient_value`)
        ) {$charset};");

        // ======= templates =======
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$tbl_templates}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `key` VARCHAR(64) NOT NULL,
            `name` VARCHAR(191) NOT NULL,
            `channel` VARCHAR(32) NOT NULL,       
            `subject` VARCHAR(255) NULL,        
            `body` MEDIUMTEXT NOT NULL,           
            `placeholders` TEXT NULL,             
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `version` VARCHAR(20) NOT NULL DEFAULT '1.0',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_tpl_key` (`key`),
            KEY `idx_channel` (`channel`),
            KEY `idx_active` (`is_active`)
        ) {$charset};");

        // ======= preferences =======
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$tbl_preferences}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `scope` ENUM('global','role','user') NOT NULL DEFAULT 'global',
            `scope_ref` VARCHAR(191) NOT NULL DEFAULT '', 
            `event_key` VARCHAR(64) NOT NULL,
            `channel` VARCHAR(32) NOT NULL,
            `enabled` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_pref` (`scope`,`scope_ref`,`event_key`,`channel`),
            KEY `idx_event` (`event_key`),
            KEY `idx_scope` (`scope`,`scope_ref`)
        ) {$charset};");

        $this->set_version(self::target_version());
    }
    public function upgrade(string $from_version): void
    {
        if ($from_version === '') {
            $this->install();
            return;
        }
        $this->set_version(self::target_version());
    }
    /* ========= Helpers (nếu cần ALTER cột) ========= */

    private function column_exists(string $table, string $column): bool
    {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $row = $this->db->get_var("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return !empty($row);
    }
}
