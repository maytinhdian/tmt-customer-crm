<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Password\Infrastructure\Migrations;

use TMT\CRM\Shared\Infrastructure\Setup\Migration\BaseMigrator;

final class PasswordMigrator extends BaseMigrator
{
    public static function module_key(): string
    {
        return 'password';
    }

    public static function target_version(): string
    {
        return '1.0.1';
    }

    public function install(): void
    {
        $collate = $this->charset_collate;
        $table   = $this->db->prefix . 'tmt_crm_passwords';

        $sql = <<<SQL
                CREATE TABLE {$table} (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    title VARCHAR(255) NOT NULL,
                    username VARCHAR(191) NULL,
                    ciphertext LONGTEXT NOT NULL,
                    nonce VARBINARY(64) NOT NULL,
                    url VARCHAR(255) NULL,
                    notes TEXT NULL,
                    subject ENUM('company','customer') NOT NULL DEFAULT 'company',
                    category VARCHAR(50) NULL,
                    owner_id BIGINT UNSIGNED NOT NULL,
                    company_id BIGINT UNSIGNED NULL,
                    customer_id BIGINT UNSIGNED NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    deleted_at DATETIME NULL,
                    PRIMARY KEY (id),
                    KEY owner_id (owner_id),
                    KEY company_id (company_id),
                    KEY customer_id (customer_id),
                    KEY deleted_at (deleted_at)
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

        $table = $this->db->prefix . 'tmt_crm_passwords';

        if (version_compare($from_version, '1.0.1', '<')) {
            // subject

            $this->db->query("ALTER TABLE `{$table}` 
                ADD COLUMN `subject` ENUM('company','customer') NOT NULL DEFAULT 'company' 
                AFTER `customer_id`");

            // category

            $this->db->query("ALTER TABLE `{$table}` 
                ADD COLUMN `category` VARCHAR(50) NULL 
                AFTER `subject`");

            // chỉ định subject dựa vào id đã có
            // (best-effort, không die nếu lỗi)
            $this->db->query("UPDATE `{$table}` SET subject='company' WHERE company_id IS NOT NULL AND company_id<>0");
            $this->db->query("UPDATE `{$table}` SET subject='customer' WHERE (company_id IS NULL OR company_id=0) AND customer_id IS NOT NULL AND customer_id<>0");
        }

        $this->set_version('1.0.1');
    }
}
