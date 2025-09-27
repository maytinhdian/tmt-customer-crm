<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Domain\Repositories\CredentialDeliveryRepositoryInterface;
use TMT\CRM\Modules\License\Application\DTO\CredentialDeliveryDTO;

final class WpdbCredentialDeliveryRepository implements CredentialDeliveryRepositoryInterface
{
    private string $table;

    public function __construct(private readonly wpdb $db)
    {
        $this->table = $this->db->prefix . 'tmt_crm_credential_deliveries';
    }

    public function list_by_credential(int $credential_id): array
    {
        $sql = $this->db->prepare("SELECT * FROM {$this->table} WHERE credential_id = %d ORDER BY delivered_at DESC", $credential_id);
        $rows = $this->db->get_results($sql, ARRAY_A) ?: [];
        return array_map(fn($r) => $this->row_to_dto($r), $rows);
    }

    public function create(CredentialDeliveryDTO $dto): int
    {
        $data = $this->dto_to_db($dto);
        $ok = $this->db->insert($this->table, $data);
        return $ok ? (int)$this->db->insert_id : 0;
    }

    /** --------- helpers --------- */

    private function row_to_dto(array $row): CredentialDeliveryDTO
    {
        return CredentialDeliveryDTO::from_array([
            'id'                       => (int)$row['id'],
            'credential_id'            => (int)$row['credential_id'],
            'delivered_to_customer_id' => $row['delivered_to_customer_id'] !== null ? (int)$row['delivered_to_customer_id'] : null,
            'delivered_to_company_id'  => $row['delivered_to_company_id']  !== null ? (int)$row['delivered_to_company_id']  : null,
            'delivered_to_contact_id'  => $row['delivered_to_contact_id']  !== null ? (int)$row['delivered_to_contact_id']  : null,
            'delivered_to_email'       => $row['delivered_to_email'] ?? null,
            'delivered_at'             => $this->fmt_datetime($row['delivered_at'] ?? null),
            'channel'                  => (string)$row['channel'],
            'delivery_note'            => $row['delivery_note'] ?? null,
        ]);
    }

    private function dto_to_db(CredentialDeliveryDTO $dto): array
    {
        return [
            'credential_id'            => $dto->credential_id,
            'delivered_to_customer_id' => $dto->delivered_to_customer_id,
            'delivered_to_company_id'  => $dto->delivered_to_company_id,
            'delivered_to_contact_id'  => $dto->delivered_to_contact_id,
            'delivered_to_email'       => $dto->delivered_to_email,
            'delivered_at'             => $this->to_mysql_datetime($dto->delivered_at ?? date('Y-m-d H:i:s')),
            'channel'                  => $dto->channel,
            'delivery_note'            => $dto->delivery_note,
        ];
    }

    private function fmt_datetime(?string $s): ?string
    {
        return $s ? (new \DateTimeImmutable($s))->format('Y-m-d H:i:s') : null;
    }
    private function to_mysql_datetime(?string $s): ?string
    {
        return $s ? (new \DateTimeImmutable($s))->format('Y-m-d H:i:s') : null;
    }
}
