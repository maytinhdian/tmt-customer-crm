<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Application\DTO;
use TMT\CRM\Shared\Traits\AsArrayTrait;

final class CredentialSeatAllocationDTO implements \JsonSerializable
{
    use AsArrayTrait;

    public ?int $id = null;
    public int $credential_id = 0;

    public string $beneficiary_type = 'company';   // company | customer | contact | email
    public ?int $beneficiary_id = null;
    public ?string $beneficiary_email = null;

    public int $seat_quota = 1;
    public int $seat_used = 0;

    public string $status = 'active';              // pending | active | revoked
    public ?string $invited_at = null;
    public ?string $accepted_at = null;
    public ?string $revoked_at = null;

    public ?string $note = null;

    public static function from_array(array $data): self
    {
        $d = new self();
        $d->id = isset($data['id']) ? (int)$data['id'] : null;
        $d->credential_id = (int)($data['credential_id'] ?? 0);
        $d->beneficiary_type = (string)($data['beneficiary_type'] ?? 'company');
        $d->beneficiary_id = isset($data['beneficiary_id']) ? (int)$data['beneficiary_id'] : null;
        $d->beneficiary_email = isset($data['beneficiary_email']) ? (string)$data['beneficiary_email'] : null;
        $d->seat_quota = isset($data['seat_quota']) ? (int)$data['seat_quota'] : 1;
        $d->seat_used = isset($data['seat_used']) ? (int)$data['seat_used'] : 0;
        $d->status = (string)($data['status'] ?? 'active');
        $d->invited_at = isset($data['invited_at']) ? (string)$data['invited_at'] : null;
        $d->accepted_at = isset($data['accepted_at']) ? (string)$data['accepted_at'] : null;
        $d->revoked_at = isset($data['revoked_at']) ? (string)$data['revoked_at'] : null;
        $d->note = isset($data['note']) ? (string)$data['note'] : null;

        return $d;
    }

     /**
     * Hỗ trợ json_encode($dto)
     */
    public function jsonSerialize(): array
    {
        return $this->to_array();
    }
}
