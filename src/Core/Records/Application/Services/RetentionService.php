<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Records\Application\Services;

/**
 * RetentionService: dọn dẹp bản ghi theo chính sách lưu trữ.
 * - Xoá archive cũ quá hạn
 * - (tuỳ chọn) xoá mềm quá hạn
 */
final class RetentionService
{
    /** Xoá bản ghi archive quá hạn (tính theo purged_at). */
    public function purge_archives_expired(int $retention_days): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'crm_archives';
        $days  = max(0, $retention_days);
        if ($days === 0) {
            return 0;
        }
        $sql = $wpdb->prepare(
            "DELETE FROM {$table} WHERE purged_at < (NOW() - INTERVAL %d DAY)",
            $days
        );
        $wpdb->query($sql);
        return (int) $wpdb->rows_affected;
    }

    /** (Tuỳ chọn) Xoá mềm quá hạn khỏi bảng nghiệp vụ - KHÔNG khuyến nghị auto xoá thật. */
    public function purge_soft_deleted_older_than(string $table, int $days): int
    {
        global $wpdb;
        $days = max(0, $days);
        if ($days === 0) {
            return 0;
        }
        // Chỉ xoá những bản ghi đã soft-delete quá hạn lâu
        $sql = $wpdb->prepare(
            "DELETE FROM {$table} WHERE deleted_at IS NOT NULL AND deleted_at < (NOW() - INTERVAL %d DAY)",
            $days
        );
        $wpdb->query($sql);
        return (int) $wpdb->rows_affected;
    }
}
