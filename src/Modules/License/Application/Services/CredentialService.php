<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Application\Services;

use TMT\CRM\Domain\Repositories\CredentialRepositoryInterface;
use TMT\CRM\Domain\Repositories\CredentialSeatAllocationRepositoryInterface;
use TMT\CRM\Domain\Repositories\CredentialActivationRepositoryInterface;
use TMT\CRM\Modules\License\Application\DTO\CredentialDTO;

/**
 * CredentialService: CRUD credential, cập nhật secrets, thống kê used/total.
 */
final class CredentialService
{
    public function __construct(
        private readonly CredentialRepositoryInterface $credential_repo,
        private readonly CredentialSeatAllocationRepositoryInterface $allocation_repo,
        private readonly CredentialActivationRepositoryInterface $activation_repo,
        private readonly CryptoService $crypto,
    ) {}

    /** Tạo mới credential (mã hoá secret + sinh mask nếu cần) */
    public function create(CredentialDTO $dto): int
    {
        if ($dto->secret_primary !== null) {
            $dto->secret_primary = $this->crypto->encrypt_secret($dto->secret_primary);
            $dto->secret_mask    = $dto->secret_mask ?: $this->crypto->make_mask($this->crypto->decrypt_secret($dto->secret_primary));
        }
        if ($dto->secret_secondary !== null) {
            $dto->secret_secondary = $this->crypto->encrypt_secret($dto->secret_secondary);
        }
        if (empty($dto->expires_at)) {
            $dto->expires_at = (new \DateTimeImmutable('now', wp_timezone()))
                ->modify('+1 year')
                ->format('Y-m-d 00:00:00');
        }
        return $this->credential_repo->create($dto);
    }

    /** Cập nhật credential (không bắt buộc đổi secrets) */
    public function update(int $id, CredentialDTO $dto): bool
    {
        // Nếu truyền secret_primary/plain mới -> mã hoá
        if ($dto->secret_primary !== null && $dto->secret_primary !== '') {
            $cipher = $this->crypto->encrypt_secret($dto->secret_primary);
            $mask   = $this->crypto->make_mask($dto->secret_primary);
            $this->credential_repo->update_secrets($id, $cipher, null, $mask);
            // reset input để không ghi đè lần 2
            $dto->secret_primary = $cipher;
            $dto->secret_mask = $mask;
        }
        if ($dto->secret_secondary !== null && $dto->secret_secondary !== '') {
            $cipher = $this->crypto->encrypt_secret($dto->secret_secondary);
            $this->credential_repo->update_secrets($id, null, $cipher, null);
            $dto->secret_secondary = $cipher;
        }
        return $this->credential_repo->update($id, $dto);
    }

    /** Xoá mềm credential */
    public function soft_delete(int $id, ?int $by = null, ?string $reason = null): bool
    {
        return $this->credential_repo->soft_delete($id, $by, $reason);
    }

    /** Đổi trạng thái */
    public function update_status(int $id, string $status): bool
    {
        return $this->credential_repo->update_status($id, $status);
    }

    /** Đổi ngày hết hạn */
    public function update_expires_at(int $id, ?string $expires_at): bool
    {
        return $this->credential_repo->update_expires_at($id, $expires_at);
    }

    /**
     * Tính Seats used/total cho 1 credential
     * @return array{used:int,total:?int,allocated:int}
     */
    public function compute_seats_overview(int $credential_id, ?int $seats_total): array
    {
        $allocs = $this->allocation_repo->list_by_credential($credential_id);
        $allocated = 0;
        $used = 0;
        foreach ($allocs as $a) {
            $allocated += $a->seat_quota;
            $used      += $a->seat_used;
        }
        return [
            'used' => $used,
            'total' => $seats_total,
            'allocated' => $allocated,
        ];
    }
}
