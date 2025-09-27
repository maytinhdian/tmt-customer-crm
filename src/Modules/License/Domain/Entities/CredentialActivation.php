<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Domain\Entities;

/**
 * Entity: Activation – một lần dùng credential trên thiết bị
 */
final class CredentialActivation
{
    public ?int $id;
    public int $credential_id;
    public ?int $allocation_id;

    // Thiết bị & người dùng
    public ?string $device_fingerprint_hash;   // SHA-256
    public ?string $hostname;
    public ?string $os_info_json;              // JSON chuỗi, để Application decode
    public ?string $location_hint;

    public ?string $user_display;
    public ?string $user_email;

    public string $status;                     // active | deactivated | transferred | blocked
    public \DateTimeInterface $activated_at;
    public ?\DateTimeInterface $deactivated_at;
    public ?\DateTimeInterface $last_seen_at;

    public string $source;                     // manual | import | api | webhook | email-parse
    public ?string $note;

    public ?int $created_by;
    public ?int $updated_by;

    public \DateTimeInterface $created_at;
    public \DateTimeInterface $updated_at;

    // Soft delete
    public ?\DateTimeInterface $deleted_at;
    public ?int $deleted_by;
    public ?string $delete_reason;

    public function __construct(
        ?int $id,
        int $credential_id,
        ?int $allocation_id,
        ?string $device_fingerprint_hash,
        ?string $hostname,
        ?string $os_info_json,
        ?string $location_hint,
        ?string $user_display,
        ?string $user_email,
        string $status,
        \DateTimeInterface $activated_at,
        ?\DateTimeInterface $deactivated_at,
        ?\DateTimeInterface $last_seen_at,
        string $source,
        ?string $note,
        ?int $created_by,
        ?int $updated_by,
        \DateTimeInterface $created_at,
        \DateTimeInterface $updated_at,
        ?\DateTimeInterface $deleted_at,
        ?int $deleted_by,
        ?string $delete_reason
    ) {
        $this->id = $id;
        $this->credential_id = $credential_id;
        $this->allocation_id = $allocation_id;
        $this->device_fingerprint_hash = $device_fingerprint_hash;
        $this->hostname = $hostname;
        $this->os_info_json = $os_info_json;
        $this->location_hint = $location_hint;
        $this->user_display = $user_display;
        $this->user_email = $user_email;
        $this->status = $status;
        $this->activated_at = $activated_at;
        $this->deactivated_at = $deactivated_at;
        $this->last_seen_at = $last_seen_at;
        $this->source = $source;
        $this->note = $note;
        $this->created_by = $created_by;
        $this->updated_by = $updated_by;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
        $this->deleted_at = $deleted_at;
        $this->deleted_by = $deleted_by;
        $this->delete_reason = $delete_reason;
    }
}
