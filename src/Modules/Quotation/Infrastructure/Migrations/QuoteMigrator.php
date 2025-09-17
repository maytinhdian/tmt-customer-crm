<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\Quotation\Infrastructure\Migrations;

use TMT\CRM\Shared\Infrastructure\Setup\Migration\BaseMigrator;

final class QuoteMigrator extends BaseMigrator
{
    public static function module_key(): string { return 'quote'; }
    public static function target_version(): string { return '1.0.0'; }

    public function install(): void
    {
        $collate = $this->charset_collate;

        // table_quotes → tmt_crm_quotes
        $table = $this->db->prefix . 'tmt_crm_quotes';
        $sql = <<<SQL
CREATE TABLE {$table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    no VARCHAR(32) NULL,
    status ENUM('draft','sent','accepted','converted','rejected','expired') NOT NULL DEFAULT 'draft',
    currency CHAR(3) NOT NULL DEFAULT 'VND',
    expires_at DATETIME NULL,
    subtotal DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    tax_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    discount_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    grand_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    note TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_company (company_id),
    KEY idx_customer (customer_id),
    KEY idx_status (status),
    KEY idx_no (no)
) {$collate};
SQL;
        dbDelta($sql);

        // table_quote_items → tmt_crm_quote_items
        $table = $this->db->prefix . 'tmt_crm_quote_items';
        $sql = <<<SQL
CREATE TABLE {$table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    quote_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    sku VARCHAR(64) NULL,
    name VARCHAR(255) NOT NULL,
    quantity DECIMAL(18,4) NOT NULL DEFAULT 1.0000,
    unit_price DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    tax_rate DECIMAL(9,4) NOT NULL DEFAULT 0.0000,
    line_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id),
    KEY idx_quote (quote_id),
    KEY idx_product (product_id)
) {$collate};
SQL;
        dbDelta($sql);

        $this->set_version(self::target_version());
    }

    public function upgrade(string $from_version): void
    {
        if ($from_version === '') { $this->install(); return; }
        $this->set_version(self::target_version());
    }
}
