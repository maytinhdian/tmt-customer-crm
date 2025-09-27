<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Application\Services;

use TMT\CRM\Domain\Repositories\CredentialRepositoryInterface;
use TMT\CRM\Domain\Repositories\CredentialSeatAllocationRepositoryInterface;
use TMT\CRM\Domain\Repositories\CredentialActivationRepositoryInterface;
use TMT\CRM\Modules\License\Application\DTO\CredentialSeatAllocationDTO;

/**
 * AllocationService: CRUD allocation, đảm bảo quota & used hợp lệ.
 */
final class AllocationService
{
    public function __construct(
        private readonly CredentialRepositoryInterface $credential_repo,
        private readonly CredentialSeatAllocationRepositoryInterface $allocation_repo,
        private readonly CredentialActivationRepositoryInterface $activation_repo,
        private readonly PolicyService $policy,
    ) {}

    /** Tạo allocation, đảm bảo tổng quota không vượt seats_total */
    public function create_allocation(CredentialSeatAllocationDTO $dto): int
    {
        $credential = $this->credential_repo->find_by_id($dto->credential_id);
        if (!$credential) return 0;

        $allocs = $this->allocation_repo->list_by_credential($dto->credential_id);
        $sum_quota = $dto->seat_quota;
        foreach ($allocs as $a) $sum_quota += $a->seat_quota;

        if (!$this->policy->can_allocate_total($credential->seats_total, $sum_quota)) {
            return 0; // hoặc ném exception tùy convention của bạn
        }
        return $this->allocation_repo->create($dto);
    }

    /** Cập nhật quota/status/note, đảm bảo used ≤ quota và tổng quota ≤ seats_total */
    public function update_allocation(int $allocation_id, CredentialSeatAllocationDTO $dto): bool
    {
        $alloc = $this->allocation_repo->find_by_id($allocation_id);
        if (!$alloc) return false;

        $credential = $this->credential_repo->find_by_id($alloc->credential_id);
        if (!$credential) return false;

        // Tính lại tổng quota nếu đổi quota
        $allocs = $this->allocation_repo->list_by_credential($alloc->credential_id);
        $sum_quota = 0;
        foreach ($allocs as $a) {
            $sum_quota += ($a->id === $allocation_id) ? $dto->seat_quota : $a->seat_quota;
        }
        if (!$this->policy->can_allocate_total($credential->seats_total, $sum_quota)) {
            return false;
        }

        // Không hạ quota thấp hơn used hiện tại
        if ($dto->seat_quota < $alloc->seat_used) {
            return false;
        }
        return $this->allocation_repo->update($allocation_id, $dto);
    }

    /** Xoá allocation (soft-delete), chỉ khi không còn activation active */
    public function delete_allocation(int $allocation_id, ?int $deleted_by = null, ?string $reason = null): bool
    {
        $alloc = $this->allocation_repo->find_by_id($allocation_id);
        if (!$alloc) return false;

        // Kiểm tra còn activation active không
        $acts = $this->activation_repo->list_by_allocation($allocation_id);
        foreach ($acts as $act) {
            if ($act->status === 'active') {
                return false;
            }
        }
        return $this->allocation_repo->delete($allocation_id, $deleted_by, $reason);
    }

    /** Đồng bộ seat_used theo số activation active (gọi sau khi add/deactivate activation) */
    public function sync_seat_used(int $allocation_id): bool
    {
        $acts = $this->activation_repo->list_by_allocation($allocation_id);
        $used = 0;
        foreach ($acts as $a) {
            if ($a->status === 'active') {
                $used++;
            }
        }
        return $this->allocation_repo->update_seat_used($allocation_id, $used);
    }
}
