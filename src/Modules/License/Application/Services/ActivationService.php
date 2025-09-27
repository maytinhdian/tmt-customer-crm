<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Application\Services;

use TMT\CRM\Domain\Repositories\CredentialRepositoryInterface;
use TMT\CRM\Domain\Repositories\CredentialSeatAllocationRepositoryInterface;
use TMT\CRM\Domain\Repositories\CredentialActivationRepositoryInterface;
use TMT\CRM\Modules\License\Application\DTO\CredentialActivationDTO;

/**
 * ActivationService: add / deactivate / transfer.
 */
final class ActivationService
{
    public function __construct(
        private readonly CredentialRepositoryInterface $credential_repo,
        private readonly CredentialSeatAllocationRepositoryInterface $allocation_repo,
        private readonly CredentialActivationRepositoryInterface $activation_repo,
        private readonly PolicyService $policy,
    ) {}

    /** Thêm activation: kiểm tra quota allocation (nếu có) */
    public function add_activation(CredentialActivationDTO $dto): int
    {
        // Nếu có allocation_id → kiểm tra quota
        if ($dto->allocation_id) {
            $alloc = $this->allocation_repo->find_by_id($dto->allocation_id);
            if (!$alloc || $alloc->status !== 'active') return 0;

            if (!$this->policy->can_add_activation_to_allocation($alloc)) {
                return 0;
            }
        }
        $new_id = $this->activation_repo->create($dto);

        // sync seat_used nếu có allocation
        if ($new_id > 0 && $dto->allocation_id) {
            $this->sync_allocation_used($dto->allocation_id);
        }
        return $new_id;
    }

    /** Deactivate 1 activation */
    public function deactivate(int $activation_id, ?string $deactivated_at = null): bool
    {
        $act = $this->activation_repo->find_by_id($activation_id);
        if (!$act) return false;

        $ok = $this->activation_repo->deactivate($activation_id, $deactivated_at);
        if ($ok && $act->allocation_id) {
            $this->sync_allocation_used($act->allocation_id);
        }
        return $ok;
    }

    /** Transfer: deactivate cũ → tạo mới (có thể cùng allocation) */
    public function transfer(int $from_activation_id, CredentialActivationDTO $new_dto): int
    {
        $from = $this->activation_repo->find_by_id($from_activation_id);
        if (!$from) return 0;

        // Nếu new_dto có allocation → kiểm tra quota
        if ($new_dto->allocation_id) {
            $alloc = $this->allocation_repo->find_by_id($new_dto->allocation_id);
            if (!$alloc || $alloc->status !== 'active') return 0;

            if (!$this->policy->can_add_activation_to_allocation($alloc)) {
                return 0;
            }
        }

        $new_id = $this->activation_repo->transfer($from_activation_id, $new_dto);

        // Sync used cho allocations liên quan
        if ($from->allocation_id) $this->sync_allocation_used($from->allocation_id);
        if ($new_dto->allocation_id && $new_dto->allocation_id !== $from->allocation_id) {
            $this->sync_allocation_used($new_dto->allocation_id);
        }
        return $new_id;
    }

    /** Ghi nhận last_seen */
    public function touch_last_seen(int $activation_id, ?string $at = null): bool
    {
        return $this->activation_repo->touch_last_seen($activation_id, $at);
    }

    private function sync_allocation_used(int $allocation_id): void
    {
        $acts = $this->activation_repo->list_by_allocation($allocation_id);
        $used = 0;
        foreach ($acts as $a) {
            if ($a->status === 'active') $used++;
        }
        $this->allocation_repo->update_seat_used($allocation_id, $used);
    }
}
