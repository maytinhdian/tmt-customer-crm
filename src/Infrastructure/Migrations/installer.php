<?php

declare(strict_types=1);

namespace TMT\CRM\Infrastructure\Migrations;

use wpdb;

final class Installer
{
    private const OPTION_DB_VERSION = 'tmt_crm_db_version';

    private wpdb $db;
    private string $table_customers;

    private function __construct(wpdb $db)
    {
        $this->db              = $db;
        $this->table_customers = $db->prefix . 'tmt_crm_customers';
    }

    /**
     * Chạy migrate nếu version hiện tại < $targetVersion
     */
    public static function run_if_needed(wpdb $db, string $targetVersion): void
    {
        $self = new self($db);
        $current = (string) get_option(self::OPTION_DB_VERSION, '');

        if ($current === $targetVersion) {
            return; // up-to-date
        }

        $self->create_or_update_customers_table();
        $self->drop_legacy_table(); // ⚠️ cẩn trọng: phá hủy bảng cũ nếu còn

        update_option(self::OPTION_DB_VERSION, $targetVersion, true);
    }

    /** Tạo/đồng bộ bảng customers */
    private function create_or_update_customers_table(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $collate = $this->db->get_charset_collate();

        // Lưu ý: với utf8mb4, index trên cột text dài nên giới hạn prefix (191) để tránh cảnh báo dbDelta
        // Tránh warning "Undefined array key 'index_type'" trong một số bản WP + MariaDB khi key không chuẩn hóa.
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
            PRIMARY KEY  (id),
            KEY type      (type),
            KEY owner_id  (owner_id),
            KEY name_idx  (name(191)),
            KEY email_idx (email),
            KEY phone_idx (phone),
            KEY company_idx (company(191))
        ) {$collate};";

        \dbDelta($sql);
    }

    /** (Tuỳ chọn) Drop bảng cũ – nếu từng dùng tên bảng khác */
    private function drop_legacy_table(): void
    {
        $legacy = $this->db->prefix . 'crm_customers';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $this->db->query("DROP TABLE IF EXISTS `{$legacy}`");
    }
}
