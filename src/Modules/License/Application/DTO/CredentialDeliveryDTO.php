<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Application\DTO;

final class CredentialDeliveryDTO
{
    public ?int $id = null;
    public int $credential_id = 0;

    public ?int $delivered_to_customer_id = null;
    public ?int $delivered_to_company_id = null;
    public ?int $delivered_to_contact_id = null;
    public ?string $delivered_to_email = null;

    public ?string $delivered_at = null; // ISO datetime string
    public string $channel = 'email';    // email | zalo | file | printed | other
    public ?string $delivery_note = null;

    public static function from_array(array $data): self
    {
        $d = new self();
        $d->id = isset($data['id']) ? (int)$data['id'] : null;
        $d->credential_id = (int)($data['credential_id'] ?? 0);

        $d->delivered_to_customer_id = isset($data['delivered_to_customer_id']) ? (int)$data['delivered_to_customer_id'] : null;
        $d->delivered_to_company_id  = isset($data['delivered_to_company_id']) ? (int)$data['delivered_to_company_id'] : null;
        $d->delivered_to_contact_id  = isset($data['delivered_to_contact_id']) ? (int)$data['delivered_to_contact_id'] : null;
        $d->delivered_to_email       = isset($data['delivered_to_email']) ? (string)$data['delivered_to_email'] : null;

        $d->delivered_at = isset($data['delivered_at']) ? (string)$data['delivered_at'] : null;
        $d->channel = (string)($data['channel'] ?? 'email');
        $d->delivery_note = isset($data['delivery_note']) ? (string)$data['delivery_note'] : null;

        return $d;
    }

    public function to_array(): array
    {
        return [
            'id' => $this->id,
            'credential_id' => $this->credential_id,
            'delivered_to_customer_id' => $this->delivered_to_customer_id,
            'delivered_to_company_id' => $this->delivered_to_company_id,
            'delivered_to_contact_id' => $this->delivered_to_contact_id,
            'delivered_to_email' => $this->delivered_to_email,
            'delivered_at' => $this->delivered_at,
            'channel' => $this->channel,
            'delivery_note' => $this->delivery_note,
        ];
    }
}
