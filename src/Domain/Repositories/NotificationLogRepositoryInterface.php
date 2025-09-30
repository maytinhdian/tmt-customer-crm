<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

interface NotificationLogRepositoryInterface
{
    /**
     * Tạo bản ghi log gửi thông báo.
     * @param array $data ['template_code','event_name','channel','recipient','subject','status','error','run_id','idempotency_key','meta','created_at']
     * @return int ID bản ghi vừa tạo
     */
    public function create(array $data): int;

    /**
     * Tìm log gần nhất theo idempotency_key trong khoảng TTL (giây).
     */
    public function find_recent_by_idempotency(string $key, int $ttl_seconds): ?array;

    /**
     * Thống kê theo ngày kể từ $since_date (YYYY-MM-DD).
     * @return array Mỗi item: ['day' => 'YYYY-MM-DD', 'channel' => 'email|notice', 'status' => 'success|fail', 'cnt' => int]
     */
    public function stats_daily(string $since_date): array;
}
