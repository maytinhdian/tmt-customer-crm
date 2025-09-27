<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Application\DTO;

final class CredentialActivationDTO
{
    public ?int $id = null;
    public int $credential_id = 0;
    public ?int $allocation_id = null;

    public ?string $device_fingerprint_hash = null;
    public ?string $hostname = null;
    public ?string $os_info_json = null;
    public ?string $location_hint = null;

    public ?string $user_display = null;
    public ?string $user_email = null;

    public string $status = 'active';
    public ?string $activated_at = null;     // ISO datetime string
    public ?string $deactivated_at = null;
    public ?string $last_seen_at = null;

    public string $source = 'manual';
    public ?string $note = null;

    public ?int $created_by = null;
    public ?int $updated_by = null;

    public static function from_array(array $data): self
    {
        $d = new self();
        $d->id = isset($data['id']) ? (int)$data['id'] : null;
        $d->credential_id = (int)($data['credential_id'] ?? 0);
        $d->allocation_id = isset($data['allocation_id']) ? (int)$data['allocation_id'] : null;

        $d->device_fingerprint_hash = isset($data['device_fingerprint_hash']) ? (string)$data['device_fingerprint_hash'] : null;
        $d->hostname = isset($data['hostname']) ? (string)$data['hostname'] : null;
        $d->os_info_json = isset($data['os_info_json']) ? (string)$data['os_info_json'] : null;
        $d->location_hint = isset($data['location_hint']) ? (string)$data['location_hint'] : null;

        $d->user_display = isset($data['user_display']) ? (string)$data['user_display'] : null;
        $d->user_email = isset($data['user_email']) ? (string)$data['user_email'] : null;

        $d->status = (string)($data['status'] ?? 'active');
        $d->activated_at = isset($data['activated_at']) ? (string)$data['activated_at'] : null;
        $d->deactivated_at = isset($data['deactivated_at']) ? (string)$data['deactivated_at'] : null;
        $d->last_seen_at = isset($data['last_seen_at']) ? (string)$data['last_seen_at'] : null;

        $d->source = (string)($data['source'] ?? 'manual');
        $d->note = isset($data['note']) ? (string)$data['note'] : null;

        $d->created_by = isset($data['created_by']) ? (int)$data['created_by'] : null;
        $d->updated_by = isset($data['updated_by']) ? (int)$data['updated_by'] : null;

        return $d;
    }

    public function to_array(): array
    {
        return [
            'id' => $this->id,
            'credential_id' => $this->credential_id,
            'allocation_id' => $this->allocation_id,
            'device_fingerprint_hash' => $this->device_fingerprint_hash,
            'hostname' => $this->hostname,
            'os_info_json' => $this->os_info_json,
            'location_hint' => $this->location_hint,
            'user_display' => $this->user_display,
            'user_email' => $this->user_email,
            'status' => $this->status,
            'activated_at' => $this->activated_at,
            'deactivated_at' => $this->deactivated_at,
            'last_seen_at' => $this->last_seen_at,
            'source' => $this->source,
            'note' => $this->note,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
        ];
    }
}
