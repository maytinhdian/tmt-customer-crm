<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Domain\Entities;

/**
 * Entity: Phân bổ ghế (seat allocation) cho đơn vị thụ hưởng (company/customer/contact/email)
 */
final class CredentialSeatAllocation
{
    public ?int $id;
    public int $credential_id;

    public string $beneficiary_type;      // company | customer | contact | email
    public ?int $beneficiary_id;          // null nếu beneficiary_type=email
    public ?string $beneficiary_email;    // dùng cho family share

    public int $seat_quota;               // số ghế cấp phát
    public int $seat_used;                // số ghế đang dùng (tính từ activations)

    public string $status;                // pending | active | revoked
    public ?\DateTimeInterface $invited_at;
    public ?\DateTimeInterface $accepted_at;
    public ?\DateTimeInterface $revoked_at;

    public ?string $note;

    public \DateTimeInterface $created_at;
    public \DateTimeInterface $updated_at;

    // Soft delete
    public ?\DateTimeInterface $deleted_at;
    public ?int $deleted_by;
    public ?string $delete_reason;

    public function __construct(
        ?int $id,
        int $credential_id,
        string $beneficiary_type,
        ?int $beneficiary_id,
        ?string $beneficiary_email,
        int $seat_quota,
        int $seat_used,
        string $status,
        ?\DateTimeInterface $invited_at,
        ?\DateTimeInterface $accepted_at,
        ?\DateTimeInterface $revoked_at,
        ?string $note,
        \DateTimeInterface $created_at,
        \DateTimeInterface $updated_at,
        ?\DateTimeInterface $deleted_at,
        ?int $deleted_by,
        ?string $delete_reason
    ) {
        $this->id = $id;
        $this->credential_id = $credential_id;
        $this->beneficiary_type = $beneficiary_type;
        $this->beneficiary_id = $beneficiary_id;
        $this->beneficiary_email = $beneficiary_email;
        $this->seat_quota = $seat_quota;
        $this->seat_used = $seat_used;
        $this->status = $status;
        $this->invited_at = $invited_at;
        $this->accepted_at = $accepted_at;
        $this->revoked_at = $revoked_at;
        $this->note = $note;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
        $this->deleted_at = $deleted_at;
        $this->deleted_by = $deleted_by;
        $this->delete_reason = $delete_reason;
    }
}
