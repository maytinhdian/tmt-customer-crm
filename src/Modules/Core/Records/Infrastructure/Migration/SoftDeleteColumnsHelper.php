<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\Core\Records\Infrastructure\Migration;

/**
 * Helper thêm cột soft-delete vào bảng bất kỳ (gọi từ migrator của từng module).
 */
final class SoftDeleteColumnsHelper
{
    public static function ensure(string $table_name): void
    {
        global $wpdb;
        $full = $wpdb->prefix . $table_name;

        $columns = $wpdb->get_col($wpdb->prepare("SHOW COLUMNS FROM {$full} LIKE %s", 'deleted_at'));
        if (empty($columns)) {
            $wpdb->query("ALTER TABLE {$full} ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL");
        }

        $columns = $wpdb->get_col($wpdb->prepare("SHOW COLUMNS FROM {$full} LIKE %s", 'deleted_by'));
        if (empty($columns)) {
            $wpdb->query("ALTER TABLE {$full} ADD COLUMN deleted_by BIGINT UNSIGNED NULL DEFAULT NULL");
        }

        $columns = $wpdb->get_col($wpdb->prepare("SHOW COLUMNS FROM {$full} LIKE %s", 'delete_reason'));
        if (empty($columns)) {
            $wpdb->query("ALTER TABLE {$full} ADD COLUMN delete_reason VARCHAR(255) NULL DEFAULT NULL");
        }

        // Index cho deleted_at để lọc nhanh
        $has_index = $wpdb->get_results("SHOW INDEX FROM {$full} WHERE Key_name = 'idx_deleted_at'");
        if (empty($has_index)) {
            $wpdb->query("ALTER TABLE {$full} ADD INDEX idx_deleted_at (deleted_at)");
        }
    }
}
