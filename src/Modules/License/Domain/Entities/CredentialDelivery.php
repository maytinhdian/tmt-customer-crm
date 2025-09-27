<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Domain\Entities;

/**
 * Entity: Lịch sử bàn giao credential cho khách
 */
final class CredentialDelivery
{
    public ?int $id;
    public int $credential_id;

    public ?int $delivered_to_customer_id;
    public ?int $delivered_to_company_id;
    public ?int $delivered_to_contact_id;
    public ?string $delivered_to_email;

    public \DateTimeInterface $delivered_at;
    public string $channel;        // email | zalo | file | printed | other
    public ?string $delivery_note;

    public \DateTimeInterface $created_at;

    public function __construct(
        ?int $id,
        int $credential_id,
        ?int $delivered_to_customer_id,
        ?int $delivered_to_company_id,
        ?int $delivered_to_contact_id,
        ?string $delivered_to_email,
        \DateTimeInterface $delivered_at,
        string $channel,
        ?string $delivery_note,
        \DateTimeInterface $created_at
    ) {
        $this->id = $id;
        $this->credential_id = $credential_id;
        $this->delivered_to_customer_id = $delivered_to_customer_id;
        $this->delivered_to_company_id = $delivered_to_company_id;
        $this->delivered_to_contact_id = $delivered_to_contact_id;
        $this->delivered_to_email = $delivered_to_email;
        $this->delivered_at = $delivered_at;
        $this->channel = $channel;
        $this->delivery_note = $delivery_note;
        $this->created_at = $created_at;
    }
}
