<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Application\Services;

use TMT\CRM\Modules\License\Application\DTO\CredentialSeatAllocationDTO;

/**
 * PolicyService: kiểm tra các ràng buộc seats/allocations/activations cơ bản.
 */
final class PolicyService
{
    /** Kiểm tra tổng quota không vượt seats_total (nếu seats_total có giá trị) */
    public function can_allocate_total(?int $seats_total, int $sum_quota_after): bool
    {
        if ($seats_total === null) return true; // không biết limit → cho phép
        return $sum_quota_after <= $seats_total;
    }

    /** Kiểm tra used ≤ quota cho một allocation */
    public function is_allocation_within_quota(CredentialSeatAllocationDTO $alloc): bool
    {
        return $alloc->seat_used <= $alloc->seat_quota;
    }

    /** Kiểm tra trước khi add activation vào allocation */
    public function can_add_activation_to_allocation(CredentialSeatAllocationDTO $alloc): bool
    {
        return ($alloc->seat_used + 1) <= $alloc->seat_quota;
    }
    public static function can_manage(): bool
    {
        return current_user_can('tmt_license_manage') || current_user_can('manage_options');
    }

    public static function can_reveal(): bool
    {
        return current_user_can('tmt_license_reveal_secret') || current_user_can('manage_options');
    }

    public static function can_delete(): bool
    {
        return current_user_can('tmt_license_delete') || current_user_can('manage_options');
    }
}
