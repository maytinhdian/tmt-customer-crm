<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Customer\Infrastructure\Migrations;

use TMT\CRM\Shared\Infrastructure\Setup\Migration\BaseMigrator;
use TMT\CRM\Core\Records\Infrastructure\Migration\SoftDeleteColumnsHelper;

final class CustomerMigrator extends BaseMigrator
{
    public static function module_key(): string
    {
        return 'customer';
    }
    public static function target_version(): string
    {
        return '1.0.2';
    }

    public function install(): void
    {
        $collate = $this->charset_collate;

        // Table từ Installer: table_customers → tmt_crm_customers
        $table = $this->db->prefix . 'tmt_crm_customers';
        $sql = <<<SQL
                    CREATE TABLE {$table} (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        owner_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(191) NULL,
                        phone VARCHAR(50) NULL,
                        address TEXT NULL,
                        note TEXT NULL,
                        created_at DATETIME NOT NULL,
                        updated_at DATETIME NOT NULL,
                        PRIMARY KEY  (id),
                        KEY owner_id  (owner_id),
                        KEY name_idx  (name(191)),
                        KEY email_idx (email)
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
        $table = $this->db->prefix . 'tmt_crm_customers';

        // Ví dụ: nếu trước đây bảng chưa có 2 cột soft-delete → thêm vào tại 1.1.0
        if (version_compare($from_version, '1.1.0', '<')) {
            // status: active/inactive
            if (!$this->column_exists($table, 'status')) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $this->db->query(
                    "ALTER TABLE `{$table}` ADD COLUMN `status` ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER `address`"
                );
            }

            // deleted_at: DATETIME NULL
            if (!$this->column_exists($table, 'deleted_at')) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $this->db->query(
                    "ALTER TABLE `{$table}` ADD COLUMN `deleted_at` DATETIME NULL AFTER `status`"
                );
            }
        }

        if (version_compare($from_version, '1.0.2', '<')) {
            // Lưu ý: helper nhận tên bảng KHÔNG prefix
            SoftDeleteColumnsHelper::ensure('tmt_crm_customers');
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
