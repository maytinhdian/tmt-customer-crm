<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Company\Infrastructure\Migrations;

use TMT\CRM\Shared\Infrastructure\Setup\Migration\BaseMigrator;

final class CompanyMigrator extends BaseMigrator
{
    public static function module_key(): string
    {
        return 'company';
    }
    public static function target_version(): string
    {
        return '1.0.0';
    }

    public function install(): void
    {
        $collate = $this->charset_collate;

        // Table từ Installer: table_companies → tmt_crm_companies
        $table = $this->db->prefix . 'tmt_crm_companies';
        $sql = <<<SQL
CREATE TABLE {$table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    owner_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(191) NULL,
    tax_code VARCHAR(50) NOT NULL,
    phone VARCHAR(50) NULL,
    address TEXT NOT NULL,
    website VARCHAR(191) NULL,
    representer VARCHAR(255) NULL,
    note TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY owner_id (owner_id),
    KEY tax_code_idx (tax_code),
    KEY name_idx (name(191)),
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
        $this->set_version(self::target_version());
    }
}
