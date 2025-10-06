<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Events\Infrastructure\Migrations;

use TMT\CRM\Shared\Infrastructure\Setup\Migration\BaseMigrator;

final class EventsMigrator extends BaseMigrator
{
    public static function module_key(): string
    {
        return 'core_events';
    }
    public static function target_version(): string
    {
        return '1.0.1';
    }

    public function install(): void
    {
        $table   = $this->db->prefix . 'tmt_crm_event_store';
        $collate = $this->charset_collate;

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id CHAR(36) NOT NULL,
            event_name VARCHAR(120) NOT NULL,
            payload_json LONGTEXT NULL,
            metadata_json LONGTEXT NULL,
            occurred_at DATETIME NOT NULL,
            actor_id BIGINT UNSIGNED NULL,
            correlation_id VARCHAR(64) NULL,
            tenant VARCHAR(64) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_event_name (event_name),
            KEY idx_correlation (correlation_id),
            KEY idx_actor (actor_id),
            KEY idx_occurred (occurred_at)
        ) {$collate};";

        dbDelta($sql);
        error_log('Create SQL ');
        $this->set_version(self::target_version());
    }

    public function upgrade(string $from_version): void
    {
        if ($from_version === '') {
            $this->install();
            return;
        }
        // // Ví dụ: từ 1.0.0 → 1.0.1 bổ sung index mới
        // if (version_compare($from_version, '1.0.1', '<')) {
        //     global $wpdb;
        //     $table = $wpdb->prefix . 'tmt_crm_event_store';
        //     if (!$this->index_exists($table, 'idx_occurred')) {
        //         $wpdb->query("ALTER TABLE {$table} ADD KEY idx_occurred (occurred_at)");
        //     }
        // }
    }

    // private function index_exists(string $table, string $index_name): bool
    // {
    //     global $wpdb;
    //     // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    //     $rows = $wpdb->get_results(
    //         $wpdb->prepare("SHOW INDEX FROM `{$table}` WHERE Key_name = %s", $index_name)
    //     );
    //     return !empty($rows);
    // }
}
