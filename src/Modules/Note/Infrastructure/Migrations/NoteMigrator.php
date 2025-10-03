<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Note\Infrastructure\Migrations;

use TMT\CRM\Shared\Infrastructure\Setup\Migration\BaseMigrator;
use TMT\CRM\Core\Records\Infrastructure\Migration\SoftDeleteColumnsHelper;

final class NoteMigrator extends BaseMigrator
{
    public static function module_key(): string
    {
        return 'note';
    }
    public static function target_version(): string
    {
        return '1.1.8';
    }

    public function install(): void
    {
        $collate = $this->charset_collate;

        // table_notes → tmt_crm_notes
        $table = $this->db->prefix . 'tmt_crm_notes';
        $sql = <<<SQL
                CREATE TABLE {$table} (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    entity_type VARCHAR(32) NOT NULL,
                    entity_id BIGINT UNSIGNED NOT NULL,
                    content LONGTEXT NULL,
                    created_by BIGINT UNSIGNED NOT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NULL,
                    PRIMARY KEY (id),
                    KEY idx_entity (entity_type, entity_id),
                    KEY idx_created_by (created_by)
                ) {$collate};
                SQL;
        dbDelta($sql);

       
        $this->set_version(self::target_version());
    }

    public function upgrade(string $from_version): void
    {
        if ($from_version === '') {
            $this->install();
            return;
        }
        $table = $this->db->prefix . 'tmt_crm_notes';

        // Ví dụ: nếu trước đây bảng chưa có 2 cột soft-delete → thêm vào tại 1.1.0
        if (version_compare($from_version, '1.1.8', '<')) {
            // status: active/inactive
            if (!$this->column_exists($table, 'status')) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $this->db->query(
                    "ALTER TABLE `{$table}` ADD COLUMN `status` ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER `content`"
                );
            }

            // Lưu ý: helper nhận tên bảng KHÔNG prefix
            SoftDeleteColumnsHelper::ensure('tmt_crm_notes');
        }
        $table = $this->db->prefix . 'tmt_crm_files';

        // Ví dụ: nếu trước đây bảng chưa có 2 cột soft-delete → thêm vào tại 1.1.0
        if (version_compare($from_version, '1.1.8', '<')) {
            // status: active/inactive
            if (!$this->column_exists($table, 'status')) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $this->db->query(
                    "ALTER TABLE `{$table}` ADD COLUMN `status` ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER `attachment_id`"
                );
            }

            // Lưu ý: helper nhận tên bảng KHÔNG prefix
            SoftDeleteColumnsHelper::ensure('tmt_crm_files');
        }
        $this->set_version(self::target_version());
    }
    /* ========= Helpers an toàn khi ALTER ========= */

    private function column_exists(string $table, string $column): bool
    {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $row = $this->db->get_var("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return !empty($row);
    }
}
