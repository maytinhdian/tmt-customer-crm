<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Core\Records\Domain\Repositories;

/**
 * Interface chung cho các Repository hỗ trợ cơ chế xoá mềm.
 *
 * Lưu ý triển khai:
 * - mark_deleted(): đặt deleted_at, deleted_by, delete_reason.
 * - restore(): đưa bản ghi về trạng thái hoạt động (clear các cột xoá mềm).
 * - purge(): xoá vĩnh viễn (DELETE) – chỉ áp dụng với bản ghi đã xoá mềm.
 * - exists_active(): kiểm tra bản ghi còn hoạt động (deleted_at IS NULL).
 */
interface SoftDeletableRepositoryInterface
{
    /**
     * Đánh dấu xoá mềm một bản ghi.
     */
    public function mark_deleted(int $id, int $actor_id, ?string $reason = null): void;

    /**
     * Khôi phục bản ghi đã xoá mềm.
     */
    public function restore(int $id, int $actor_id): void;

    /**
     * Xoá vĩnh viễn bản ghi (DELETE).
     * Nên chỉ cho phép khi bản ghi đang ở trạng thái xoá mềm.
     */
    public function purge(int $id, int $actor_id): void;

    /**
     * Kiểm tra bản ghi có đang hoạt động (chưa bị xoá mềm) hay không.
     */
    public function exists_active(int $id): bool;
}
