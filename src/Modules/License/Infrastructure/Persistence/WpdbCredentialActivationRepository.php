<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\License\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Domain\Repositories\CredentialActivationRepositoryInterface;
use TMT\CRM\Modules\License\Application\DTO\CredentialActivationDTO;

final class WpdbCredentialActivationRepository implements CredentialActivationRepositoryInterface
{
    private string $table;

    public function __construct(private readonly wpdb $db)
    {
        $this->table = $this->db->prefix . 'tmt_crm_credential_activations';
    }

    public function find_by_id(int $id): ?CredentialActivationDTO
    {
        $sql = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d AND deleted_at IS NULL", $id);
        $row = $this->db->get_row($sql, ARRAY_A);
        return $row ? $this->row_to_dto($row) : null;
    }

    public function list_by_credential(int $credential_id): array
    {
        $sql = $this->db->prepare("SELECT * FROM {$this->table} WHERE credential_id = %d AND deleted_at IS NULL ORDER BY id DESC", $credential_id);
        $rows = $this->db->get_results($sql, ARRAY_A) ?: [];
        return array_map(fn($r) => $this->row_to_dto($r), $rows);
    }

    public function list_by_allocation(int $allocation_id): array
    {
        $sql = $this->db->prepare("SELECT * FROM {$this->table} WHERE allocation_id = %d AND deleted_at IS NULL ORDER BY id DESC", $allocation_id);
        $rows = $this->db->get_results($sql, ARRAY_A) ?: [];
        return array_map(fn($r) => $this->row_to_dto($r), $rows);
    }

    public function create(CredentialActivationDTO $dto): int
    {
        $data = $this->dto_to_db($dto);
        $ok = $this->db->insert($this->table, $data);
        return $ok ? (int)$this->db->insert_id : 0;
    }

    public function deactivate(int $id, ?string $deactivated_at = null): bool
    {
        $ok = $this->db->update($this->table, [
            'status'         => 'deactivated',
            'deactivated_at' => $this->to_mysql_datetime($deactivated_at ?? date('Y-m-d H:i:s')),
        ], ['id' => $id]);
        return $ok !== false;
    }

    public function transfer(int $from_activation_id, CredentialActivationDTO $new_dto): int
    {
        // Deactivate old
        $this->deactivate($from_activation_id, date('Y-m-d H:i:s'));
        // Create new
        return $this->create($new_dto);
    }

    public function touch_last_seen(int $id, ?string $at = null): bool
    {
        $ok = $this->db->update($this->table, [
            'last_seen_at' => $this->to_mysql_datetime($at ?? date('Y-m-d H:i:s'))
        ], ['id' => $id]);
        return $ok !== false;
    }

    /** --------- helpers --------- */

    private function row_to_dto(array $row): CredentialActivationDTO
    {
        return CredentialActivationDTO::from_array([
            'id'                      => (int)$row['id'],
            'credential_id'           => (int)$row['credential_id'],
            'allocation_id'           => $row['allocation_id'] !== null ? (int)$row['allocation_id'] : null,
            'device_fingerprint_hash' => $row['device_fingerprint_hash'] ?? null,
            'hostname'                => $row['hostname'] ?? null,
            'os_info_json'            => $row['os_info'] ?? null,
            'location_hint'           => $row['location_hint'] ?? null,
            'user_display'            => $row['user_display'] ?? null,
            'user_email'              => $row['user_email'] ?? null,
            'status'                  => (string)$row['status'],
            'activated_at'            => $this->fmt_datetime($row['activated_at'] ?? null),
            'deactivated_at'          => $this->fmt_datetime($row['deactivated_at'] ?? null),
            'last_seen_at'            => $this->fmt_datetime($row['last_seen_at'] ?? null),
            'source'                  => (string)$row['source'],
            'note'                    => $row['note'] ?? null,
            'created_by'              => $row['created_by'] !== null ? (int)$row['created_by'] : null,
            'updated_by'              => $row['updated_by'] !== null ? (int)$row['updated_by'] : null,
        ]);
    }

    private function dto_to_db(CredentialActivationDTO $dto): array
    {
        return [
            'credential_id'           => $dto->credential_id,
            'allocation_id'           => $dto->allocation_id,
            'device_fingerprint_hash' => $dto->device_fingerprint_hash,
            'hostname'                => $dto->hostname,
            'os_info'                 => $dto->os_info_json,
            'location_hint'           => $dto->location_hint,
            'user_display'            => $dto->user_display,
            'user_email'              => $dto->user_email,
            'status'                  => $dto->status,
            'activated_at'            => $this->to_mysql_datetime($dto->activated_at ?? date('Y-m-d H:i:s')),
            'deactivated_at'          => $this->to_mysql_datetime($dto->deactivated_at),
            'last_seen_at'            => $this->to_mysql_datetime($dto->last_seen_at),
            'source'                  => $dto->source,
            'note'                    => $dto->note,
            'created_by'              => $dto->created_by,
            'updated_by'              => $dto->updated_by,
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
