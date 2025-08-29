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
    private string $table_contacts;
    private string $table_history;

    private function __construct(wpdb $db)
    {
        $this->db              = $db;
        $this->table_customers = $db->prefix . 'tmt_crm_customers';
        $this->table_companies = $db->prefix . 'tmt_crm_companies';
        $this->table_contacts =  $db->prefix . 'tmt_crm_company_contacts';
        $this->table_history =  $db->prefix . 'tmt_crm_customer_company_history';
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
        $self->create_or_update_contacts_table();
        $self->create_or_update_history_table();

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


    // 1) Bảng liên hệ công ty: role + thời gian hiệu lực
    private function create_or_update_contacts_table(): void
    {
        $charset = $this->db->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_contacts} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NOT NULL,
            customer_id BIGINT UNSIGNED NOT NULL,
            role VARCHAR(40) NOT NULL,             
            title VARCHAR(191) NULL,               
            is_primary TINYINT(1) NOT NULL DEFAULT 0,
            start_date DATE NULL,
            end_date DATE NULL,
            note TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY  (id),
            KEY idx_company (company_id),
            KEY idx_customer (customer_id),
            KEY idx_role (role),
            KEY idx_company_role_primary (company_id, role, is_primary),
            KEY idx_active (end_date, start_date)
        ) {$charset};";

        dbDelta($sql);
    }

    // 2) Bảng lịch sử làm việc Customer ↔ Company
    private function create_or_update_history_table(): void
    {
        $charset = $this->db->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_history} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NOT NULL,
            company_id BIGINT UNSIGNED NOT NULL,
            title VARCHAR(191) NULL,               -- chức danh (VD: Kế toán trưởng, Trưởng phòng mua)
            start_date DATE NULL,
            end_date DATE NULL,
            note TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY  (id),
            KEY idx_customer (customer_id),
            KEY idx_company (company_id),
            KEY idx_period (start_date, end_date)
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
