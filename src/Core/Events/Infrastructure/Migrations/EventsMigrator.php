<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Events\Infrastructure\Migrations;

use TMT\CRM\Shared\Infrastructure\Setup\Migration\BaseMigrator;

/**
 * Migrator cho Core/Events: tạo/ nâng cấp bảng lưu nhật ký sự kiện (event store).
 *
 * - Table: {$wpdb->prefix}tmt_crm_event_store
 * - Mục tiêu: audit trail, hỗ trợ debug/trace, (tương lai) replay/analytics.
 *
 * Quy ước:
 * - Không ép kiểu JSON của MySQL để đảm bảo tương thích hosting cũ → dùng LONGTEXT.
 * - Các chỉ số (INDEX) cần thiết: name, occurred_at.
 */
final class EventsMigrator extends BaseMigrator
{
    /** Dùng để ghi vào option version cho module (theo convention của BaseMigrator) */
    public static function module_key(): string
    {
        // Nên đặt key có tiền tố core_ để phân biệt module nghiệp vụ
        return 'core_events';
    }

    /** Tăng phiên bản này khi có thay đổi cấu trúc DB cần migrate */
    public static function target_version(): string
    {
        return '1.0.0';
    }

    /**
     * Cài đặt mới (fresh install).
     * Tạo bảng event store.
     */
    public function install(): void
    {
        $table = $this->db->prefix . 'tmt_crm_event_store';
        $collate = $this->charset_collate;

        // Lưu ý: dùng LONGTEXT để tương thích rộng; metadata/payload là JSON string.
        $sql = <<<SQL
                CREATE TABLE {$table} (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    event_id VARCHAR(64) NOT NULL,                 -- UUID hoặc mã sự kiện duy nhất trong hệ thống
                    name VARCHAR(100) NOT NULL,                    -- Tên sự kiện (VD: CompanyCreated)
                    occurred_at DATETIME NOT NULL,                 -- UTC
                    payload LONGTEXT NULL,                         -- JSON dữ liệu (DTO được json_encode)
                    metadata LONGTEXT NULL,                        -- JSON metadata (actor_id, correlation_id, tenant,...)
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_event_name (name),
                    KEY idx_event_occurred_at (occurred_at)
                ) {$collate};
                SQL;

        // Ưu tiên dbDelta để an toàn tên cột/chỉ số (nếu BaseMigrator không wrap sẵn)
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Nâng cấp từ version cũ → mới.
     * Ghi chú: ví dụ minh họa các bước ALTER an toàn; cập nhật
     * theo nhu cầu thật tế khi bạn thay đổi cấu trúc.
     */
    public function upgrade(string $from_version): void
    {
        $table = $this->db->prefix . 'tmt_crm_event_store';

        // Ví dụ: thêm chỉ số nếu trước đây chưa tạo
        if (version_compare($from_version, '1.0.0', '<')) {
            $this->ensure_index($table, 'idx_event_name', 'name');
            $this->ensure_index($table, 'idx_event_occurred_at', 'occurred_at');
        }

        // Ví dụ để dành:
        // if (version_compare($from_version, '1.0.1', '<')) {
        //     // Thêm cột mới nếu cần (VD: tenant)
        //     if (!$this->column_exists($table, 'tenant')) {
        //         // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        //         $this->db->query(
        //             "ALTER TABLE `{$table}` ADD COLUMN `tenant` VARCHAR(100) NULL AFTER `metadata`"
        //         );
        //     }
        //     $this->ensure_index($table, 'idx_event_tenant', 'tenant');
        // }
    }

    /**
     * Đảm bảo tồn tại INDEX (nếu chưa có) cho 1 cột.
     */
    private function ensure_index(string $table, string $index_name, string $column): void
    {
        if ($this->index_exists($table, $index_name)) {
            return;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $this->db->query(sprintf(
            "ALTER TABLE `%s` ADD INDEX `%s` (`%s`)",
            esc_sql($table),
            esc_sql($index_name),
            esc_sql($column)
        ));
    }

    /**
     * Kiểm tra index có tồn tại không.
     */
    private function index_exists(string $table, string $index_name): bool
    {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $this->db->get_results(
            $this->db->prepare("SHOW INDEX FROM `{$table}` WHERE Key_name = %s", $index_name)
        );
        return !empty($rows);
    }
}
