<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\Core\Records\Application\Services;

use RuntimeException;

/**
 * TrashService gom xoá mềm / khôi phục / purge.
 * LƯU Ý: Service này không truy cập DB trực tiếp cho entity nghiệp vụ.
 * Các module nghiệp vụ nên tự implement repository riêng cho entity của mình.
 */
final class TrashService
{
    public function __construct(
        private HistoryService $history
    ) {}

    /**
     * Xoá mềm — nghiệp vụ sẽ tự gọi repository tương ứng để đánh dấu deleted_at,...
     * Ở đây chỉ đề xuất chữ ký và nơi xử lý cross-cutting nếu cần.
     */
    public function soft_delete(string $entity, int $id, int $actor_id, ?string $reason = null): void
    {
        // Gợi ý: $repo = $this->resolve_repo($entity); $repo->mark_deleted($id, $actor_id, $reason);
        // Cross-cutting: ghi audit event ngắn nếu muốn (SOFT_DELETE)
    }

    /** Khôi phục từ xoá mềm. */
    public function restore(string $entity, int $id, int $actor_id): void
    {
        // Gợi ý: $repo = $this->resolve_repo($entity); $repo->restore($id, $actor_id);
        // Cross-cutting: ghi audit event ngắn nếu muốn (RESTORE)
    }

    /**
     * Purge (xoá thật) — cần truyền builder để tạo snapshot/relations/attachments.
     * $snapshot_builder: fn(int $id): array{snapshot: array, relations?: array|null, attachments?: array|null}
     */
    public function purge(
        string $entity,
        int $id,
        int $actor_id,
        callable $snapshot_builder,
        ?string $reason = null,
        ?string $ip = null,
        ?string $ua = null
    ): void {
        $payload = $snapshot_builder($id);
        if (!is_array($payload) || !isset($payload['snapshot']) || !is_array($payload['snapshot'])) {
            throw new RuntimeException('Snapshot builder trả về dữ liệu không hợp lệ.');
        }

        $relations   = $payload['relations']   ?? null;
        $attachments = $payload['attachments'] ?? null;

        // 1) Ghi archive + audit
        $this->history->snapshot_and_log_purge(
            $entity,
            $id,
            $payload['snapshot'],
            $relations,
            $attachments,
            $actor_id,
            $reason,
            $ip,
            $ua
        );

        // 2) Nghiệp vụ tự DELETE: vd. $repo->purge($id, $actor_id);
    }
}
