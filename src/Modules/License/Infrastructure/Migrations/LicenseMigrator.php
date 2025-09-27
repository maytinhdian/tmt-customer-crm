<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Infrastructure\Migrations;

use TMT\CRM\Shared\Infrastructure\Setup\Migration\BaseMigrator;

final class LicenseMigrator extends BaseMigrator
{
    public static function module_key(): string
    {
        return 'license';
    }

    public static function target_version(): string
    {
        return '1.0.1';
    }

    public function install(): void
    {
        $collate = $this->charset_collate;
        $table_credentials   = $this->db->prefix . 'tmt_crm_credentials';
        $table_credential_seat_allocations   = $this->db->prefix . 'tmt_crm_credential_seat_allocations';
        $table_credential_activations = $this->db->prefix . 'tmt_crm_credential_activations';
        $table_credential_deliveries = $this->db->prefix . 'tmt_crm_credential_deliveries';
        // 1) credentials
        $sql1 = <<<SQL
                    CREATE TABLE {$table_credentials} (
                        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        `number` VARCHAR(50) NOT NULL,
                        `type` ENUM('LICENSE_KEY','EMAIL_ACCOUNT','SAAS_ACCOUNT','API_TOKEN','WIFI_ACCOUNT','OTHER') NOT NULL DEFAULT 'LICENSE_KEY',
                        `label` VARCHAR(190) NOT NULL,
                        `customer_id` BIGINT UNSIGNED NULL,
                        `company_id` BIGINT UNSIGNED NULL,
                        `status` ENUM('active','disabled','expired','revoked','pending') NOT NULL DEFAULT 'active',
                        `expires_at` DATETIME NULL,
                        `seats_total` INT NULL,
                        `sharing_mode` ENUM('none','seat_allocation','family_share') NOT NULL DEFAULT 'none',
                        `renewal_of_id` BIGINT UNSIGNED NULL,
                        `owner_id` BIGINT UNSIGNED NULL,

                        `secret_primary_encrypted` LONGTEXT NULL,
                        `secret_secondary_encrypted` LONGTEXT NULL,
                        `username` VARCHAR(190) NULL,
                        `extra_json_encrypted` LONGTEXT NULL,
                        `secret_mask` VARCHAR(255) NULL,

                        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                        `deleted_at` DATETIME NULL,
                        `deleted_by` BIGINT UNSIGNED NULL,
                        `delete_reason` VARCHAR(255) NULL,

                        PRIMARY KEY (`id`),
                        UNIQUE KEY `ux_credentials_number` (`number`),
                        KEY `idx_credentials_customer` (`customer_id`),
                        KEY `idx_credentials_company` (`company_id`),
                        KEY `idx_credentials_status` (`status`),
                        KEY `idx_credentials_expires` (`expires_at`),
                        KEY `idx_credentials_renewal` (`renewal_of_id`)
                    ) {$collate};
                SQL;


        // 2) seat allocations
        $sql2 = <<<SQL
                    CREATE TABLE {$table_credential_seat_allocations} (
                        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        `credential_id` BIGINT UNSIGNED NOT NULL,
                        `beneficiary_type` ENUM('company','customer','contact','email') NOT NULL,
                        `beneficiary_id` BIGINT UNSIGNED NULL,
                        `beneficiary_email` VARCHAR(190) NULL,
                        `seat_quota` INT NOT NULL DEFAULT 1,
                        `seat_used` INT NOT NULL DEFAULT 0,
                        `status` ENUM('pending','active','revoked') NOT NULL DEFAULT 'active',
                        `invited_at` DATETIME NULL,
                        `accepted_at` DATETIME NULL,
                        `revoked_at` DATETIME NULL,
                        `note` VARCHAR(255) NULL,

                        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                        `deleted_at` DATETIME NULL,
                        `deleted_by` BIGINT UNSIGNED NULL,
                        `delete_reason` VARCHAR(255) NULL,

                        PRIMARY KEY (`id`),
                        KEY `idx_alloc_credential` (`credential_id`),
                        KEY `idx_alloc_status` (`status`),
                        KEY `idx_alloc_beneficiary` (`beneficiary_type`, `beneficiary_id`),
                        KEY `idx_alloc_email` (`beneficiary_email`)
                    ) {$collate};
                 SQL;

        // 3) activations
        $sql3 = <<<SQL
            CREATE TABLE {$table_credential_activations}(
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `credential_id` BIGINT UNSIGNED NOT NULL,
                `allocation_id` BIGINT UNSIGNED NULL,
                `device_fingerprint_hash` CHAR(64) NULL,
                `hostname` VARCHAR(190) NULL,
                `os_info` JSON NULL,
                `location_hint` VARCHAR(190) NULL,
                `user_display` VARCHAR(190) NULL,
                `user_email` VARCHAR(190) NULL,
                `status` ENUM('active','deactivated','transferred','blocked') NOT NULL DEFAULT 'active',
                `activated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `deactivated_at` DATETIME NULL,
                `last_seen_at` DATETIME NULL,
                `source` ENUM('manual','import','api','webhook','email-parse') NOT NULL DEFAULT 'manual',
                `note` VARCHAR(255) NULL,
                `created_by` BIGINT UNSIGNED NULL,
                `updated_by` BIGINT UNSIGNED NULL,

                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                `deleted_at` DATETIME NULL,
                `deleted_by` BIGINT UNSIGNED NULL,
                `delete_reason` VARCHAR(255) NULL,

                PRIMARY KEY (`id`),
                KEY `idx_act_credential` (`credential_id`),
                KEY `idx_act_allocation` (`allocation_id`),
                KEY `idx_act_status` (`status`),
                KEY `idx_act_fingerprint` (`device_fingerprint_hash`),
                KEY `idx_act_hostname` (`hostname`)
            ) {$collate};
        SQL;

        // 4) deliveries
        $sql4 = <<<SQL
                    CREATE TABLE {$table_credential_deliveries} (
                        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        `credential_id` BIGINT UNSIGNED NOT NULL,
                        `delivered_to_customer_id` BIGINT UNSIGNED NULL,
                        `delivered_to_company_id` BIGINT UNSIGNED NULL,
                        `delivered_to_contact_id` BIGINT UNSIGNED NULL,
                        `delivered_to_email` VARCHAR(190) NULL,
                        `delivered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `channel` ENUM('email','zalo','file','printed','other') NOT NULL DEFAULT 'email',
                        `delivery_note` VARCHAR(255) NULL,

                        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                        PRIMARY KEY (`id`),
                        KEY `idx_deliv_credential` (`credential_id`),
                        KEY `idx_deliv_time` (`delivered_at`)
                    ) {$collate};
                SQL;

        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
        dbDelta($sql4);

        $this->set_version(self::target_version());
    }
    public function upgrade(string $from_version): void
    {
        if ($from_version === '') {
            $this->install();
            return;
        }
        $this->set_version('1.0.1');
    }
}
