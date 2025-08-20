<?php

namespace TMT\CRM\Infrastructure\Migrations;

use wpdb;

final class Installer
{
    public function __construct(private ?wpdb $db = null)
    {
        global $wpdb;
        $this->db = $this->db ?: $wpdb;
    }

    public function run(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $this->db->get_charset_collate();

        // Customers
        $sql_customers = "CREATE TABLE {$this->db->prefix}crm_customers (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            full_name VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            email VARCHAR(255) NOT NULL,
            company_id BIGINT UNSIGNED NULL,
            address TEXT NULL,
            tags TEXT NULL,
            note TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id)
        ) $charset;";

        // Companies
        $sql_companies = "CREATE TABLE {$this->db->prefix}crm_companies (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            tax_code VARCHAR(50) NULL,
            address TEXT NULL,
            contact_person VARCHAR(255) NULL,
            phone VARCHAR(50) NULL,
            email VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id)
        ) $charset;";

        // Quotations
        $sql_quotations = "CREATE TABLE {$this->db->prefix}crm_quotations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NOT NULL,
            total DECIMAL(15,2) NOT NULL,
            status VARCHAR(50) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id)
        ) $charset;";

        // Invoices
        $sql_invoices = "CREATE TABLE {$this->db->prefix}crm_invoices (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            quotation_id BIGINT UNSIGNED NULL,
            customer_id BIGINT UNSIGNED NOT NULL,
            total DECIMAL(15,2) NOT NULL,
            paid DECIMAL(15,2) NOT NULL DEFAULT 0,
            status VARCHAR(50) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id)
        ) $charset;";

        // Debts
        $sql_debts = "CREATE TABLE {$this->db->prefix}crm_debts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            invoice_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            due_date DATETIME NOT NULL,
            paid TINYINT(1) DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id)
        ) $charset;";

        // payments (NEW)
        $sql_payments = "CREATE TABLE {$this->db->prefix}crm_payments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            invoice_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            note TEXT NULL,
            paid_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset;";


        dbDelta($sql_customers);
        dbDelta($sql_companies);
        dbDelta($sql_quotations);
        dbDelta($sql_invoices);
        dbDelta($sql_debts);
        dbDelta($sql_payments);
    }
}
