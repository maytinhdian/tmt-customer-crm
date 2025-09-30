<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Numbering\Infrastructure\Migrations;

use TMT\CRM\Shared\Infrastructure\Setup\Migration\BaseMigrator;

final class NumberingMigrator extends BaseMigrator
{
    public static function module_key(): string
    {
        return 'numbering';
    }

    public static function target_version(): string
    {
        return '1.0.1';
    }

    public function install(): void
    {
        $collate = $this->charset_collate;
        $table   = $this->db->prefix . 'tmt_crm_numbering_rules';

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            entity_type VARCHAR(50) NOT NULL UNIQUE,
            prefix VARCHAR(50) DEFAULT '',
            suffix VARCHAR(50) DEFAULT '',
            padding TINYINT UNSIGNED DEFAULT 4,
            reset ENUM('never','yearly','monthly') DEFAULT 'never',
            last_number INT UNSIGNED DEFAULT 0,
            year_key INT UNSIGNED DEFAULT 0,
            month_key TINYINT UNSIGNED DEFAULT 0,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$collate};";

        dbDelta($sql);

        $this->set_version(self::target_version());
    }

    public function upgrade(string $from_version): void
    {
        if ($from_version === '') {
            $this->install();
            return;
        }

        $table = $this->db->prefix . 'tmt_crm_numbering_rules';

        if (version_compare($from_version, '1.0.1', '<')) {
        }

        $this->set_version('1.0.1');
    }
}
