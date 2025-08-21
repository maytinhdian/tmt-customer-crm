<?php

declare(strict_types=1);

namespace TMT\CRM\Infrastructure\Migrations;

use wpdb;

final class Installer
{
    private const OPTION_DB_VERSION = 'tmt_crm_db_version';
    private const TARGET_VERSION    = '1.2.0'; // bump version để chạy 1 lần

    private wpdb $db;
    private string $table_customers;

    public function __construct(wpdb $db)
    {
        $this->db             = $db;
        $this->table_customers = $db->prefix . 'tmt_crm_customers';
    }

    public function run(): void
    {
        $current = get_option(self::OPTION_DB_VERSION, '');
        if (version_compare($current, self::TARGET_VERSION, '<')) {
            $this->create_or_update_customers_table();

            // (tuỳ chọn) nếu muốn DROP bảng cũ ngay tại đây, bật dòng dưới:
            $this->drop_legacy_table(); // ⚠️ PHÁ HỦY DỮ LIỆU BẢNG CŨ

            update_option(self::OPTION_DB_VERSION, self::TARGET_VERSION, true);
        }
    }

    /** Tạo/đồng bộ bảng mới */
    private function create_or_update_customers_table(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $collate = $this->db->get_charset_collate();
        $sql = "CREATE TABLE {$this->table_customers} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(20) NOT NULL DEFAULT 'individual',
            owner_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(191) NULL,
            phone VARCHAR(50) NULL,
            company VARCHAR(255) NULL,
            vat_code VARCHAR(50) NULL,
            address TEXT NULL,
            note TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY type (type),
            KEY owner_id (owner_id),
            KEY name_idx (name),
            KEY email_idx (email),
            KEY phone_idx (phone),
            KEY company_idx (company)
        ) {$collate};";

        \dbDelta($sql);
    }

    /** (Tuỳ chọn) Drop bảng cũ – dùng khi chắc chắn KHÔNG còn cần dữ liệu cũ */
    private function drop_legacy_table(): void
    {
        $legacy = $this->db->prefix . 'crm_customers';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $this->db->query("DROP TABLE IF EXISTS {$legacy}");
    }
}
