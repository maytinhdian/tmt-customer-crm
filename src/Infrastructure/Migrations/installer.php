<?php

declare(strict_types=1);

namespace TMT\CRM\Infrastructure\Migrations;

use wpdb;

final class Installer
{
    private const OPTION_DB_VERSION = 'tmt_crm_db_version';

    private wpdb $db;

    // Bảng sẵn có
    private string $table_customers;
    private string $table_companies;
    private string $table_contacts;
    private string $table_history;

    // Bảng mới: Quote/Order/Invoice/Payment/Sequence
    private string $table_quotes;
    private string $table_quote_items;
    private string $table_orders;
    private string $table_order_items;
    private string $table_invoices;
    private string $table_invoice_items;
    private string $table_payments;
    private string $table_payment_allocations;
    private string $table_sequences;

    private function __construct(wpdb $db)
    {
        $this->db = $db;

        // Đang dùng tiền tố tmt_crm_* trong code hiện hữu
        $this->table_customers = $db->prefix . 'tmt_crm_customers';
        $this->table_companies = $db->prefix . 'tmt_crm_companies';
        $this->table_contacts  = $db->prefix . 'tmt_crm_company_contacts';
        $this->table_history   = $db->prefix . 'tmt_crm_customer_company_history';

        // Định danh các bảng mới
        $this->table_quotes               = $db->prefix . 'tmt_crm_quotes';
        $this->table_quote_items          = $db->prefix . 'tmt_crm_quote_items';
        $this->table_orders               = $db->prefix . 'tmt_crm_orders';
        $this->table_order_items          = $db->prefix . 'tmt_crm_order_items';
        $this->table_invoices             = $db->prefix . 'tmt_crm_invoices';
        $this->table_invoice_items        = $db->prefix . 'tmt_crm_invoice_items';
        $this->table_payments             = $db->prefix . 'tmt_crm_payments';
        $this->table_payment_allocations  = $db->prefix . 'tmt_crm_payment_allocations';
        $this->table_sequences            = $db->prefix . 'tmt_crm_sequences';
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

        // Bảng hiện hữu
        $self->create_or_update_customers_table();
        $self->create_or_update_companies_table();
        $self->create_or_update_contacts_table();
        $self->create_or_update_history_table();

        // ===== BỔ SUNG MỚI: QUOTE → ORDER → INVOICE + PAYMENTS + SEQUENCES =====
        $self->create_or_update_sequences_table();
        $self->create_or_update_quotes_tables();
        $self->create_or_update_orders_tables();
        $self->create_or_update_invoices_tables();
        $self->create_or_update_payments_tables();

        // $self->drop_legacy_table(); // ⚠️ cẩn trọng: chỉ dùng nếu chắc chắn cần xoá bảng cũ

        update_option(self::OPTION_DB_VERSION, $targetVersion, true);
    }

    /** Tạo/đồng bộ bảng customers */
    private function create_or_update_customers_table(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collate = $this->db->get_charset_collate();

        // Lưu ý: với utf8mb4, index trên cột text dài nên giới hạn prefix (191)
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
            owner_id BIGINT UNSIGNED NULL,
            representer VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_tax_code (tax_code),
            KEY idx_name (name(191)),
            KEY idx_email (email)
        ) {$collate};";

        \dbDelta($sql);
    }

    /** Tạo/đồng bộ bảng quan hệ Company–Customer (contact + role + primary) */
    private function create_or_update_contacts_table(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collate = $this->db->get_charset_collate();

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
        ) {$collate};";

        \dbDelta($sql);
    }

    /** Lịch sử gắn KH ↔ Công ty (employment/history) */
    private function create_or_update_history_table(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collate = $this->db->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_history} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NOT NULL,
            company_id BIGINT UNSIGNED NOT NULL,
            title VARCHAR(191) NULL,               -- chức danh (VD: Kế toán trưởng)
            start_date DATE NULL,
            end_date DATE NULL,
            note TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY  (id),
            KEY idx_customer (customer_id),
            KEY idx_company (company_id),
            KEY idx_period (start_date, end_date)
        ) {$collate};";

        \dbDelta($sql);
    }

    // ======================= PHẦN MỚI: QUOTE / ORDER / INVOICE / PAYMENT =======================

    /** Sequence đánh số chứng từ (type + period(YYYYMM) → last_no) */
    private function create_or_update_sequences_table(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collate = $this->db->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_sequences} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type ENUM('quote','order','invoice','payment') NOT NULL,
            period CHAR(6) NOT NULL, -- YYYYMM
            last_no INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uq_type_period (type, period)
        ) {$collate};";

        \dbDelta($sql);
    }

    /** Quotes & Quote Items */
    private function create_or_update_quotes_tables(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collate = $this->db->get_charset_collate();

        $quotes = "CREATE TABLE {$this->table_quotes} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            code VARCHAR(32) NOT NULL,
            status ENUM('draft','sent','accepted','converted','rejected','expired') NOT NULL DEFAULT 'draft',
            customer_id BIGINT UNSIGNED NULL,
            company_id BIGINT UNSIGNED NULL,
            owner_id BIGINT UNSIGNED NULL,
            currency CHAR(3) NOT NULL DEFAULT 'VND',
            expires_at DATE NULL,
            note TEXT NULL,
            subtotal DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            discount_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            tax_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            grand_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_quote_code (code),
            KEY idx_quote_customer (customer_id),
            KEY idx_quote_company (company_id),
            KEY idx_quote_owner (owner_id),
            KEY idx_quote_status (status),
            KEY idx_quote_expires (expires_at)
        ) {$collate};";

        \dbDelta($quotes);

        $items = "CREATE TABLE {$this->table_quote_items} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            quote_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NULL,
            sku VARCHAR(64) NULL,
            name VARCHAR(255) NOT NULL,
            qty DECIMAL(18,3) NOT NULL DEFAULT 1,
            unit_price DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            discount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00, -- %
            line_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            PRIMARY KEY (id),
            KEY idx_qi_quote (quote_id),
            KEY idx_qi_sku (sku),
            KEY idx_qi_name (name(191))
        ) {$collate};";

        \dbDelta($items);
    }

    /** Orders & Order Items */
    private function create_or_update_orders_tables(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collate = $this->db->get_charset_collate();

        $orders = "CREATE TABLE {$this->table_orders} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            code VARCHAR(32) NOT NULL,
            status ENUM('draft','confirmed','packed','shipped','delivered','completed','cancelled') NOT NULL DEFAULT 'draft',
            customer_id BIGINT UNSIGNED NULL,
            company_id BIGINT UNSIGNED NULL,
            owner_id BIGINT UNSIGNED NULL,
            currency CHAR(3) NOT NULL DEFAULT 'VND',
            note TEXT NULL,
            subtotal DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            discount_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            tax_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            grand_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_order_code (code),
            KEY idx_order_customer (customer_id),
            KEY idx_order_company (company_id),
            KEY idx_order_owner (owner_id),
            KEY idx_order_status (status)
        ) {$collate};";

        \dbDelta($orders);

        $items = "CREATE TABLE {$this->table_order_items} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NULL,
            sku VARCHAR(64) NULL,
            name VARCHAR(255) NOT NULL,
            qty DECIMAL(18,3) NOT NULL DEFAULT 1,
            unit_price DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            discount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            line_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            PRIMARY KEY (id),
            KEY idx_oi_order (order_id),
            KEY idx_oi_sku (sku),
            KEY idx_oi_name (name(191))
        ) {$collate};";

        \dbDelta($items);
    }

    /** Invoices & Invoice Items */
    private function create_or_update_invoices_tables(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collate = $this->db->get_charset_collate();

        $invoices = "CREATE TABLE {$this->table_invoices} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            code VARCHAR(32) NOT NULL,
            status ENUM('draft','issued','paid_partially','paid','voided') NOT NULL DEFAULT 'draft',
            order_id BIGINT UNSIGNED NULL,
            customer_id BIGINT UNSIGNED NULL,
            company_id BIGINT UNSIGNED NULL,
            owner_id BIGINT UNSIGNED NULL,
            currency CHAR(3) NOT NULL DEFAULT 'VND',
            issued_at DATETIME NULL,
            due_date DATE NULL,
            note TEXT NULL,
            subtotal DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            discount_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            tax_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            grand_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_invoice_code (code),
            KEY idx_inv_order (order_id),
            KEY idx_inv_customer (customer_id),
            KEY idx_inv_status (status),
            KEY idx_inv_issued (issued_at),
            KEY idx_inv_due (due_date)
        ) {$collate};";

        \dbDelta($invoices);

        $items = "CREATE TABLE {$this->table_invoice_items} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            invoice_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NULL,
            sku VARCHAR(64) NULL,
            name VARCHAR(255) NOT NULL,
            qty DECIMAL(18,3) NOT NULL DEFAULT 1,
            unit_price DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            discount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            line_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            PRIMARY KEY (id),
            KEY idx_ii_invoice (invoice_id),
            KEY idx_ii_sku (sku),
            KEY idx_ii_name (name(191))
        ) {$collate};";

        \dbDelta($items);
    }

    /** Payments & Allocations (cấn trừ nhiều hoá đơn) */
    private function create_or_update_payments_tables(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collate = $this->db->get_charset_collate();

        $payments = "CREATE TABLE {$this->table_payments} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            code VARCHAR(32) NOT NULL,
            status ENUM('draft','completed','voided') NOT NULL DEFAULT 'completed',
            method VARCHAR(50) NULL,
            amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            currency CHAR(3) NOT NULL DEFAULT 'VND',
            note TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_payment_code (code),
            KEY idx_pay_status (status),
            KEY idx_pay_amount (amount)
        ) {$collate};";

        \dbDelta($payments);

        $alloc = "CREATE TABLE {$this->table_payment_allocations} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            payment_id BIGINT UNSIGNED NOT NULL,
            invoice_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            PRIMARY KEY (id),
            KEY idx_alloc_payment (payment_id),
            KEY idx_alloc_invoice (invoice_id)
        ) {$collate};";

        \dbDelta($alloc);
    }

    /** (Tuỳ chọn) Drop bảng cũ – nếu từng dùng tên bảng khác */
    private function drop_legacy_table(): void
    {
        $legacy = $this->db->prefix . 'crm_customers';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $this->db->query("DROP TABLE IF EXISTS `{$legacy}`");
    }
}
