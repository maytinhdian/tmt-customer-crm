<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\Contact\Infrastructure\Migrations;

use TMT\CRM\Shared\Infrastructure\Setup\Migration\BaseMigrator;
use TMT\CRM\Core\Records\Infrastructure\Migration\SoftDeleteColumnsHelper; 

final class ContactMigrator extends BaseMigrator
{
    public static function module_key(): string { return 'contact'; }
    public static function target_version(): string { return '1.0.2'; }

    public function install(): void
    {
        $collate = $this->charset_collate;

        // Table từ Installer: table_contacts → tmt_crm_company_contacts
        $table = $this->db->prefix . 'tmt_crm_company_contacts';
        $sql = <<<SQL
CREATE TABLE {$table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    role VARCHAR(40) NOT NULL,
    title VARCHAR(191) NULL,
    is_primary TINYINT(1) NULL DEFAULT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    note TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY  (id),
    KEY idx_company (company_id),
    KEY idx_customer (customer_id),
    KEY idx_role (role),
    KEY idx_company_role_primary (company_id, role, is_primary),
    KEY idx_active (end_date, start_date)
) {$collate};
SQL;
        dbDelta($sql);

        // Table từ Installer: table_history → tmt_crm_customer_company_history
        $table = $this->db->prefix . 'tmt_crm_customer_company_history';
        $sql = <<<SQL
CREATE TABLE {$table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    action ENUM('attach','detach','set_primary','clear_primary','update') NOT NULL,
    note TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    PRIMARY KEY (id),
    KEY idx_company (company_id),
    KEY idx_customer (customer_id),
    KEY idx_action (action),
    KEY idx_time (created_at)
) {$collate};
SQL;
        dbDelta($sql);

        $this->set_version(self::target_version());
    }

    public function upgrade(string $from_version): void
    {
        if ($from_version === '') { $this->install(); return; }
          // Lưu ý: helper nhận tên bảng KHÔNG prefix
        SoftDeleteColumnsHelper::ensure('tmt_crm_company_contacts');
        $this->set_version(self::target_version());
    }
}
