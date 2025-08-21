<?php

namespace TMT\CRM\Infrastructure\Migrations;

use wpdb;

defined('ABSPATH') || exit;

/**
 * Installer / Migrator cho module Customers
 * - Tạo bảng mới: {$wpdb->prefix}tmt_crm_customers
 * - Di trú dữ liệu từ bảng cũ {$wpdb->prefix}crm_customers (nếu có)
 * - Thêm FULLTEXT (nếu DB hỗ trợ)
 */
final class Installer
{
    private wpdb $db;
    private string $new_table;
    private string $legacy_table;

    public function __construct(?wpdb $db = null)
    {
        global $wpdb;
        $this->db          = $db ?: $wpdb;
        $this->new_table   = $this->db->prefix . 'tmt_crm_customers';
        $this->legacy_table = $this->db->prefix . 'crm_customers';
    }

    /** Gọi trong register_activation_hook hoặc routine nâng cấp */
    public function run(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $this->create_new_table();
        $this->migrate_legacy_customers();
        $this->maybe_add_fulltext_index();
    }

    /** Tạo bảng mới đúng theo Repository hiện tại */
    private function create_new_table(): void
    {
        $charset = $this->db->get_charset_collate();

        // dbDelta-friendly: từng dòng, KEY đặt tên, types rõ ràng.
        $sql = "CREATE TABLE {$this->new_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            email VARCHAR(191) NULL,
            phone VARCHAR(50) NULL,
            company VARCHAR(191) NULL,
            address TEXT NULL,
            note TEXT NULL,
            type VARCHAR(50) NULL,
            owner_id BIGINT(20) UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY type (type),
            KEY owner_id (owner_id),
            KEY name_idx (name),
            KEY email_idx (email),
            KEY phone_idx (phone),
            KEY company_idx (company)
        ) {$charset};";

        \dbDelta($sql);
    }

    /**
     * Di trú dữ liệu từ bảng cũ (nếu tồn tại) sang bảng mới.
     * Map:
     * - full_name  -> name
     * - email, phone, address, note giữ nguyên nếu có
     * - company_id -> bỏ (schema mới dùng 'company' là tên tự do)
     * - tags       -> bỏ
     * - updated_at -> nếu null, set = created_at
     */
    private function migrate_legacy_customers(): void
    {
        // Nếu không có bảng cũ thì bỏ qua
        $legacy_exists = $this->table_exists($this->legacy_table);
        if (!$legacy_exists) {
            return;
        }

        // Tránh migrate trùng nhiều lần: nếu bảng mới đã có dữ liệu thì thôi
        $new_has_rows = (int)$this->db->get_var("SELECT COUNT(1) FROM {$this->new_table}") > 0;
        if ($new_has_rows) {
            return;
        }

        // Kiểm tra cột của bảng cũ có đúng kỳ vọng không
        $cols = $this->db->get_col("SHOW COLUMNS FROM {$this->legacy_table}", 0);
        $needed = ['id', 'full_name', 'phone', 'email', 'address', 'note', 'created_at', 'updated_at'];
        foreach ($needed as $col) {
            if (!in_array($col, $cols, true)) {
                // Bảng cũ không đúng schema kỳ vọng → không migrate để an toàn
                return;
            }
        }

        // Chuyển dữ liệu: company để NULL, type/owner_id để NULL.
        // updated_at nếu NULL → dùng created_at.
        $sql = "
            INSERT INTO {$this->new_table}
                (id, name, email, phone, company, address, note, type, owner_id, created_at, updated_at)
            SELECT
                id,
                full_name AS name,
                NULLIF(email, '') AS email,
                NULLIF(phone, '') AS phone,
                NULL,                        -- company (schema cũ: company_id) → bỏ
                NULLIF(address, '') AS address,
                NULLIF(note, '') AS note,
                NULL,                        -- type
                NULL,                        -- owner_id
                created_at,
                COALESCE(updated_at, created_at) AS updated_at
            FROM {$this->legacy_table};
        ";

        // Nếu fail thì bỏ qua (không gây fatal)
        @$this->db->query($sql);
    }

    /** Thêm FULLTEXT nếu engine hỗ trợ */
    private function maybe_add_fulltext_index(): void
    {
        // Kiểm tra đã có FULLTEXT chưa
        $has = $this->db->get_var($this->db->prepare(
            "SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_TYPE = 'FULLTEXT'",
            \DB_NAME,
            $this->new_table
        ));
        if ($has) return;

        // Thử thêm FULLTEXT (InnoDB MySQL 5.6+)
        $sql = "ALTER TABLE {$this->new_table}
                ADD FULLTEXT KEY tmt_crm_customers_ft (name, email, phone, company)";
        @$this->db->query($sql);
    }

    private function table_exists(string $table): bool
    {
        $table = esc_sql($table);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return (bool) $this->db->get_var($this->db->prepare(
            "SELECT COUNT(1) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
            \DB_NAME,
            $table
        ));
    }
}
