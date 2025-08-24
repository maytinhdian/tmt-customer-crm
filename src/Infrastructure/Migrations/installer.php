<?php

declare(strict_types=1);

namespace TMT\CRM\Infrastructure\Migrations;

use wpdb;

final class Installer
{
    private const OPTION_DB_VERSION = 'tmt_crm_db_version';

    private wpdb $db;
    private string $table_customers;
    private string $table_companies;
    private string $table_employments;
    private string $table_contact_roles;

    private function __construct(wpdb $db)
    {
        $this->db              = $db;
        $this->table_customers = $db->prefix . 'tmt_crm_customers';
        $this->table_companies = $db->prefix . 'tmt_crm_companies';
        $this->table_employments = $db->prefix . 'tmt_crm_customer_employments';
        $this->table_contact_roles = $db->prefix . 'tmt_crm_company_contact_roles';
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
        $self->create_or_update_companies_table();
        $self->create_or_update_employments_table();
        $self->create_or_update_contact_roles_table();

        // $self->drop_legacy_table(); // ⚠️ cẩn trọng: phá hủy bảng cũ nếu còn

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
        ) {$collate};";

        \dbDelta($sql);
    }


    /** Tạo/đồng bộ bảng companies (bắt buộc: name, tax_code, address) */
    private function create_or_update_companies_table(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $collate = $this->db->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_companies} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,          
            tax_code VARCHAR(50) NOT NULL,      
            phone VARCHAR(50) NULL,
            email VARCHAR(191) NULL,
            address TEXT NOT NULL,              
            website VARCHAR(255) NULL,
            note TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_tax_code (tax_code),
            KEY idx_name (name(191)),
            KEY idx_email (email)
        ) {$collate};";

        \dbDelta($sql);
    }

    /*** Tạo/ đồng bộ bảng customer_employment_tables */

    private function create_or_update_employments_table(): void
    {
        $charset = $this->db->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_employments} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NOT NULL,
            company_id  BIGINT UNSIGNED NOT NULL,
            start_date  DATE NOT NULL,
            end_date    DATE NULL,
            is_primary  TINYINT(1) NOT NULL DEFAULT 1,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_active_company (company_id, end_date),
            KEY idx_customer (customer_id, start_date)
        ) {$charset};";

        dbDelta($sql);
    }

    /*** Tạo/ đồng bộ bảng company_contact_roles tables */
    private function create_or_update_contact_roles_table(): void
    {
        $charset = $this->db->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_contact_roles} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id   BIGINT UNSIGNED NOT NULL,
            customer_id  BIGINT UNSIGNED NOT NULL,
            role         VARCHAR(64) NOT NULL,
            start_date   DATE NOT NULL,
            end_date     DATE NULL,
            created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_role_active (company_id, role, end_date),
            KEY idx_customer_role (customer_id, role, start_date)
        ) {$charset};";

        dbDelta($sql);
    }

    /** (Tuỳ chọn) Drop bảng cũ – nếu từng dùng tên bảng khác */
    private function drop_legacy_table(): void
    {
        $legacy = $this->db->prefix . 'crm_customers';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $this->db->query("DROP TABLE IF EXISTS `{$legacy}`");
    }
}
