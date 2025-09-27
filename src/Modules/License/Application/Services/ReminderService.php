<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Application\Services;

use TMT\CRM\Domain\Repositories\CredentialRepositoryInterface;

/**
 * ReminderService: P0 chỉ cung cấp hàm quét credentials sắp hết hạn.
 * Cron & gửi thông báo bạn có thể nối sau.
 */
final class ReminderService
{
    public function __construct(private readonly CredentialRepositoryInterface $credential_repo) {}

    /**
     * Trả về danh sách credentials sắp hết hạn trong N ngày (status != 'revoked')
     * @return array{items: array<int, array>, total:int}
     */
    public function find_expiring_within_days(int $days, int $page = 1, int $per_page = 50): array
    {
        $filter = [
            'expiring_within_days' => $days,
            'exclude_status' => 'revoked',
        ];
        return $this->credential_repo->search($filter, $page, $per_page);
    }
}
