<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Workflow\Infrastructure\Migration;

use TMT\CRM\Core\Workflow\WorkflowModule;

/** Tạo bảng lưu workflow */
final class WorkflowMigrator
{
    public static function install_or_upgrade(): void
    {
        $installed = get_option(WorkflowModule::OPTION_VERSION);
        if ($installed === WorkflowModule::VERSION) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'tmt_crm_workflows';
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(191) NOT NULL,
            slug VARCHAR(191) NOT NULL UNIQUE,
            enabled TINYINT(1) NOT NULL DEFAULT 1,
            trigger_key VARCHAR(191) NOT NULL,
            conditions_json LONGTEXT NULL,
            actions_json LONGTEXT NULL,
            metadata_json LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            KEY enabled (enabled),
            KEY trigger_key (trigger_key)
        ) {$charset_collate};";

        dbDelta($sql);
        update_option(WorkflowModule::OPTION_VERSION, WorkflowModule::VERSION);
    }
}
