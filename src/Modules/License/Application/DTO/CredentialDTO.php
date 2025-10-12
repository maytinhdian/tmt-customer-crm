<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Application\DTO;

use TMT\CRM\Shared\Traits\AsArrayTrait;

final class CredentialDTO implements \JsonSerializable
{
    use AsArrayTrait;

    public ?int $id = null;
    public string $number = '';
    public string $type = 'LICENSE_KEY';
    public string $label = '';

    public ?int $customer_id = null;
    public ?int $company_id = null;

    public string $status = 'active';
    public ?string $expires_at = null;   // ISO datetime string

    public ?int $seats_total = null;
    public string $sharing_mode = 'none';
    public ?int $renewal_of_id = null;

    public ?int $owner_id = null;

    public ?string $secret_primary = null;
    public ?string $secret_secondary = null;
    public ?string $username = null;
    public ?string $extra_json = null;
    public ?string $secret_mask = null;

    public static function from_array(array $data): self
    {
        $d = new self();
        $d->id = isset($data['id']) ? (int)$data['id'] : null;
        $d->number = (string)($data['number'] ?? '');
        $d->type = (string)($data['type'] ?? 'LICENSE_KEY');
        $d->label = (string)($data['label'] ?? '');

        $d->customer_id = isset($data['customer_id']) ? (int)$data['customer_id'] : null;
        $d->company_id  = isset($data['company_id']) ? (int)$data['company_id'] : null;

        $d->status = (string)($data['status'] ?? 'active');
        $d->expires_at = isset($data['expires_at']) ? (string)$data['expires_at'] : null;

        $d->seats_total = isset($data['seats_total']) ? (int)$data['seats_total'] : null;
        $d->sharing_mode = (string)($data['sharing_mode'] ?? 'none');
        $d->renewal_of_id = isset($data['renewal_of_id']) ? (int)$data['renewal_of_id'] : null;

        $d->owner_id = isset($data['owner_id']) ? (int)$data['owner_id'] : null;

        $d->secret_primary = isset($data['secret_primary']) ? (string)$data['secret_primary'] : null;
        $d->secret_secondary = isset($data['secret_secondary']) ? (string)$data['secret_secondary'] : null;
        $d->username = isset($data['username']) ? (string)$data['username'] : null;
        $d->extra_json = isset($data['extra_json']) ? (string)$data['extra_json'] : null;
        $d->secret_mask = isset($data['secret_mask']) ? (string)$data['secret_mask'] : null;

        return $d;
    }
    /**
     * Hỗ trợ json_encode($dto)
     */
    public function jsonSerialize(): array
    {
        return $this->to_array();
    }
    // public function to_array(): array
    // {
    //     return [
    //         'id' => $this->id,
    //         'number' => $this->number,
    //         'type' => $this->type,
    //         'label' => $this->label,
    //         'customer_id' => $this->customer_id,
    //         'company_id' => $this->company_id,
    //         'status' => $this->status,
    //         'expires_at' => $this->expires_at,
    //         'seats_total' => $this->seats_total,
    //         'sharing_mode' => $this->sharing_mode,
    //         'renewal_of_id' => $this->renewal_of_id,
    //         'owner_id' => $this->owner_id,
    //         'secret_primary' => $this->secret_primary,
    //         'secret_secondary' => $this->secret_secondary,
    //         'username' => $this->username,
    //         'extra_json' => $this->extra_json,
    //         'secret_mask' => $this->secret_mask,
    //     ];
    // }
}
