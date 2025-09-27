<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\License\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Domain\Repositories\CredentialSeatAllocationRepositoryInterface;
use TMT\CRM\Modules\License\Application\DTO\CredentialSeatAllocationDTO;

final class WpdbCredentialSeatAllocationRepository implements CredentialSeatAllocationRepositoryInterface
{
    private string $table;

    public function __construct(private readonly wpdb $db)
    {
        $this->table = $this->db->prefix . 'tmt_crm_credential_seat_allocations';
    }

    public function list_by_credential(int $credential_id): array
    {
        $sql = $this->db->prepare("SELECT * FROM {$this->table} WHERE credential_id = %d AND deleted_at IS NULL ORDER BY id ASC", $credential_id);
        $rows = $this->db->get_results($sql, ARRAY_A) ?: [];
        return array_map(fn($r) => $this->row_to_dto($r), $rows);
    }

    public function find_by_id(int $allocation_id): ?CredentialSeatAllocationDTO
    {
        $sql = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d AND deleted_at IS NULL", $allocation_id);
        $row = $this->db->get_row($sql, ARRAY_A);
        return $row ? $this->row_to_dto($row) : null;
    }

    public function create(CredentialSeatAllocationDTO $dto): int
    {
        $data = $this->dto_to_db($dto);
        $ok = $this->db->insert($this->table, $data);
        return $ok ? (int)$this->db->insert_id : 0;
    }

    public function update(int $allocation_id, CredentialSeatAllocationDTO $dto): bool
    {
        $data = $this->dto_to_db($dto);
        $ok = $this->db->update($this->table, $data, ['id' => $allocation_id]);
        return $ok !== false;
    }

    public function delete(int $allocation_id, ?int $deleted_by = null, ?string $reason = null): bool
    {
        $ok = $this->db->update($this->table, [
            'deleted_at'   => current_time('mysql'),
            'deleted_by'   => $deleted_by,
            'delete_reason'=> $reason,
        ], ['id' => $allocation_id]);
        return $ok !== false;
    }

    public function update_seat_used(int $allocation_id, int $seat_used): bool
    {
        $ok = $this->db->update($this->table, ['seat_used' => $seat_used], ['id' => $allocation_id]);
        return $ok !== false;
    }

    /** --------- helpers --------- */

    private function row_to_dto(array $row): CredentialSeatAllocationDTO
    {
        return CredentialSeatAllocationDTO::from_array([
            'id'                => (int)$row['id'],
            'credential_id'     => (int)$row['credential_id'],
            'beneficiary_type'  => (string)$row['beneficiary_type'],
            'beneficiary_id'    => $row['beneficiary_id'] !== null ? (int)$row['beneficiary_id'] : null,
            'beneficiary_email' => $row['beneficiary_email'] ?? null,
            'seat_quota'        => (int)$row['seat_quota'],
            'seat_used'         => (int)$row['seat_used'],
            'status'            => (string)$row['status'],
            'invited_at'        => $this->fmt_datetime($row['invited_at'] ?? null),
            'accepted_at'       => $this->fmt_datetime($row['accepted_at'] ?? null),
            'revoked_at'        => $this->fmt_datetime($row['revoked_at'] ?? null),
            'note'              => $row['note'] ?? null,
        ]);
    }

    private function dto_to_db(CredentialSeatAllocationDTO $dto): array
    {
        return [
            'credential_id'     => $dto->credential_id,
            'beneficiary_type'  => $dto->beneficiary_type,
            'beneficiary_id'    => $dto->beneficiary_id,
            'beneficiary_email' => $dto->beneficiary_email,
            'seat_quota'        => $dto->seat_quota,
            'seat_used'         => $dto->seat_used,
            'status'            => $dto->status,
            'invited_at'        => $this->to_mysql_datetime($dto->invited_at),
            'accepted_at'       => $this->to_mysql_datetime($dto->accepted_at),
            'revoked_at'        => $this->to_mysql_datetime($dto->revoked_at),
            'note'              => $dto->note,
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
