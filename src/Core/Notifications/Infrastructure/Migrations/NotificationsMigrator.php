<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Infrastructure\Migrations;

use TMT\CRM\Shared\Infrastructure\Setup\Migration\BaseMigrator;

/**
 * NotificationsMigrator
 * - Tạo 3 bảng P1: logs, templates, preferences
 * - Gọi trong NotificationsModule::boot() hoặc Installer chung
 */
final class NotificationsMigrator extends BaseMigrator
{
    public static function module_key(): string
    {
        return 'notifications';
    }
    public static function target_version(): string
    {
        return '1.1.0';
    }

    public function install(): void
    {
        $collate = $this->charset_collate;

        // 1) Bảng log gửi thông báo
        $table = $this->db->prefix . 'tmt_crm_notification_logs';
        $sql = <<<SQL
                CREATE TABLE {$table} (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    template_code VARCHAR(120) NULL,
                    event_name VARCHAR(120) NOT NULL,
                    channel VARCHAR(50) NOT NULL,
                    recipient VARCHAR(190) NOT NULL,
                    subject TEXT NULL,
                    status ENUM('success','fail') NOT NULL,
                    error TEXT NULL,
                    run_id VARCHAR(64) NULL,
                    idempotency_key CHAR(64) NOT NULL,
                    meta LONGTEXT NULL,
                    created_at DATETIME NOT NULL,
                    PRIMARY KEY (id),
                    KEY idx_event (event_name),
                    KEY idx_channel (channel),
                    KEY idx_created (created_at),
                    KEY idx_idem (idempotency_key),
                    KEY idx_run (run_id)
                ) {$collate};
                SQL;
        dbDelta($sql);

        // 2) Bảng template thông báo
        $table = $this->db->prefix . 'tmt_crm_notification_templates';
        $sql = <<<SQL
                CREATE TABLE {$table} (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    code VARCHAR(120) NOT NULL,
                    channel VARCHAR(50) NOT NULL,
                    subject_tpl LONGTEXT NULL,
                    body_tpl LONGTEXT NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    updated_at DATETIME NOT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY uniq_code (code),
                    KEY idx_channel (channel),
                    KEY idx_updated (updated_at)
                ) {$collate};
                SQL;
        dbDelta($sql);

        // 3) Bảng tùy chọn người dùng (bật/tắt kênh, quiet hours)
        $table = $this->db->prefix . 'tmt_crm_notification_preferences';
        $sql = <<<SQL
                CREATE TABLE {$table} (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    user_id BIGINT UNSIGNED NOT NULL,
                    event_name VARCHAR(120) NOT NULL,
                    channel VARCHAR(50) NOT NULL,
                    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
                    quiet_hours VARCHAR(50) NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY uniq_user_event_channel (user_id, event_name, channel),
                    KEY idx_user (user_id)
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

        // Ví dụ nâng cấp từng phần khi tăng version sau này
        // if (version_compare($from_version, '1.1.0', '<')) { ... }

        $this->install(); // P1: idempotent (dbDelta) → gọi lại để đảm bảo lược đồ
        $this->set_version(self::target_version());
    }
}
