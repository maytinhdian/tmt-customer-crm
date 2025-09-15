<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Note\Infrastructure\Migration;

use TMT\CRM\Infrastructure\Setup\Migration\BaseMigrator;

final class NoteMigrator extends BaseMigrator
{
    public static function module_key(): string
    {
        return 'note';
    }
    public static function target_version(): string
    {
        return '1.0.0';
    }

    public function install(): void
    {
        $collate = $this->charset_collate;

        // table_notes → tmt_crm_notes
        $table = $this->db->prefix . 'tmt_crm_notes';
        $sql = <<<SQL
CREATE TABLE {$table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entity_type VARCHAR(32) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    content LONGTEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_entity (entity_type, entity_id),
    KEY idx_created_by (created_by)
) {$collate};
SQL;
        dbDelta($sql);

        // table_files → tmt_crm_files
        $table = $this->db->prefix . 'tmt_crm_files';
        $sql = <<<SQL
CREATE TABLE {$table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entity_type VARCHAR(32) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    attachment_id BIGINT UNSIGNED NOT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    description VARCHAR(191) NULL,
    PRIMARY KEY (id),
    KEY idx_entity (entity_type, entity_id),
    KEY idx_uploaded_by (uploaded_by),
    KEY idx_attachment (attachment_id)
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
