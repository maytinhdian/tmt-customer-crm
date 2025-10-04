<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Log\Infrastructure\Migrations;

use TMT\CRM\Shared\Infrastructure\Setup\Migration\BaseMigrator;

final class LogMigrator extends BaseMigrator
{
    public static function module_key(): string
    {
        return 'log';
    }
    public static function target_version(): string
    {
        return '0.1.0';
    }

    public function install(): void
    {
        $db = $this->db;
        $charset = $this->charset_collate;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = $db->prefix . 'tmt_crm_logs';
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
