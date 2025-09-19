<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Records\Infrastructure\Persistence\Wpdb\Traits;

use wpdb;

trait WpdbSoftDeleteTrait
{
    /** @var wpdb */
    protected wpdb $db;

    /** Tên bảng KHÔNG prefix, ví dụ: 'tmt_crm_companies' */
    protected string $table;

    protected function full_table(): string
    {
        return $this->db->prefix . $this->table;
    }

    /** Đánh dấu xoá mềm */
    public function mark_deleted(int $id, int $actor_id, ?string $reason = null): void
    {
        $full = $this->full_table();
        $now  = current_time('mysql');

        // Chỉ update khi chưa bị xoá mềm
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $sql = $this->db->prepare("SELECT deleted_at FROM `{$full}` WHERE id = %d", $id);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $deleted_at = $this->db->get_var($sql);
        if (!empty($deleted_at)) {
            // đã ở trạng thái xoá mềm → coi như xong
            return;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
        $ok = $this->db->update(
            $full,
            [
                'status'        => 'inactive',
                'deleted_at'    => $now,
                'deleted_by'    => $actor_id,
                'delete_reason' => $reason,
                'restored_at'   => null,
                'restored_by'   => null,
                'updated_at'    => $now,
            ],
            ['id' => $id],
            ['%s','%s','%d','%s','%s','%d','%s'],
            ['%d']
        );

        if ($ok === false) {
            throw new \RuntimeException('Không thể xoá mềm bản ghi #' . $id);
        }
    }

    /** Khôi phục bản ghi đã xoá mềm */
    public function restore(int $id, int $actor_id): void
    {
        $full = $this->full_table();
        $now  = current_time('mysql');

        // Chỉ restore khi đang xoá mềm
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $sql = $this->db->prepare("SELECT deleted_at FROM `{$full}` WHERE id = %d", $id);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $deleted_at = $this->db->get_var($sql);
        if (empty($deleted_at)) {
            // đang active → coi như xong
            return;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
        $ok = $this->db->update(
            $full,
            [
                'status'        => 'active',
                'deleted_at'    => null,
                'deleted_by'    => null,
                'delete_reason' => null,
                'restored_at'   => $now,
                'restored_by'   => $actor_id,
                'updated_at'    => $now,
            ],
            ['id' => $id],
            ['%s','%s','%s','%s','%s','%d','%s'],
            ['%d']
        );

        if ($ok === false) {
            throw new \RuntimeException('Không thể khôi phục bản ghi #' . $id);
        }
    }

    /** Xoá vĩnh viễn – chỉ khi đang xoá mềm */
    public function purge(int $id, int $actor_id): void
    {
        $full = $this->full_table();

        // xác nhận đang xoá mềm
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $sql = $this->db->prepare("SELECT deleted_at FROM `{$full}` WHERE id = %d", $id);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $deleted_at = $this->db->get_var($sql);
        if (empty($deleted_at)) {
            throw new \RuntimeException('Chỉ được purge bản ghi đã xoá mềm #' . $id);
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
        $ok = $this->db->delete($full, ['id' => $id], ['%d']);
        if ($ok === false) {
            throw new \RuntimeException('Không thể xoá vĩnh viễn bản ghi #' . $id);
        }
    }

    /** Bản ghi còn hoạt động? (deleted_at IS NULL) */
    public function exists_active(int $id): bool
    {
        $full = $this->full_table();
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $sql = $this->db->prepare("SELECT 1 FROM `{$full}` WHERE id = %d AND deleted_at IS NULL", $id);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return (bool) $this->db->get_var($sql);
    }
}
