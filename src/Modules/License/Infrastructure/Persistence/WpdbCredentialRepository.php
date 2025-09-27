<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\License\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Domain\Repositories\CredentialRepositoryInterface;
use TMT\CRM\Modules\License\Application\DTO\CredentialDTO;

final class WpdbCredentialRepository implements CredentialRepositoryInterface
{
    private string $table;

    public function __construct(private readonly wpdb $db)
    {
        $this->table = $this->db->prefix . 'tmt_crm_credentials';
    }

    /** ---------- Public API ---------- */

    public function find_by_id(int $id): ?CredentialDTO
    {
        $sql = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d AND deleted_at IS NULL", $id);
        $row = $this->db->get_row($sql, ARRAY_A);
        return $row ? $this->row_to_dto($row) : null;
    }

    public function find_by_number(string $number): ?CredentialDTO
    {
        $sql = $this->db->prepare("SELECT * FROM {$this->table} WHERE number = %s AND deleted_at IS NULL", $number);
        $row = $this->db->get_row($sql, ARRAY_A);
        return $row ? $this->row_to_dto($row) : null;
    }

    public function search(array $filter, int $page = 1, int $per_page = 20): array
    {
        $where = ["deleted_at IS NULL"];
        $args  = [];

        if (!empty($filter['q'])) {
            $where[] = "(label LIKE %s OR number LIKE %s)";
            $like = '%' . $this->db->esc_like((string)$filter['q']) . '%';
            $args[] = $like; $args[] = $like;
        }
        if (!empty($filter['type'])) {
            $where[] = "type = %s";
            $args[] = (string)$filter['type'];
        }
        if (!empty($filter['status'])) {
            $where[] = "status = %s";
            $args[] = (string)$filter['status'];
        }

        $where_sql = 'WHERE ' . implode(' AND ', $where);
        $offset = max(0, ($page - 1) * $per_page);

        $sql_items = $this->db->prepare(
            "SELECT * FROM {$this->table} {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d",
            array_merge($args, [$per_page, $offset])
        );
        $rows = $this->db->get_results($sql_items, ARRAY_A) ?: [];

        // total
        $sql_total = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} {$where_sql}", $args);
        $total = (int)$this->db->get_var($sql_total);

        return [
            'items' => array_map(fn($r) => $this->row_to_dto($r), $rows),
            'total' => $total,
        ];
    }

    public function create(CredentialDTO $dto): int
    {
        $data = $this->dto_to_db($dto);
        $ok = $this->db->insert($this->table, $data);
        return $ok ? (int)$this->db->insert_id : 0;
    }

    public function update(int $id, CredentialDTO $dto): bool
    {
        $data = $this->dto_to_db($dto);
        $ok = $this->db->update($this->table, $data, ['id' => $id]);
        return $ok !== false;
    }

    public function soft_delete(int $id, ?int $deleted_by = null, ?string $reason = null): bool
    {
        $ok = $this->db->update($this->table, [
            'deleted_at'   => current_time('mysql'),
            'deleted_by'   => $deleted_by,
            'delete_reason'=> $reason,
        ], ['id' => $id]);
        return $ok !== false;
    }

    public function update_secrets(int $id, ?string $secret_primary_encrypted, ?string $secret_secondary_encrypted, ?string $secret_mask): bool
    {
        $ok = $this->db->update($this->table, [
            'secret_primary_encrypted'   => $secret_primary_encrypted,
            'secret_secondary_encrypted' => $secret_secondary_encrypted,
            'secret_mask'                => $secret_mask,
        ], ['id' => $id]);
        return $ok !== false;
    }

    public function update_status(int $id, string $status): bool
    {
        $ok = $this->db->update($this->table, ['status' => $status], ['id' => $id]);
        return $ok !== false;
    }

    public function update_expires_at(int $id, ?string $expires_at): bool
    {
        $ok = $this->db->update($this->table, [
            'expires_at' => $expires_at ? $this->to_mysql_datetime($expires_at) : null
        ], ['id' => $id]);
        return $ok !== false;
    }

    /** ---------- Inline mapping helpers ---------- */

    private function row_to_dto(array $row): CredentialDTO
    {
        return CredentialDTO::from_array([
            'id'             => (int)$row['id'],
            'number'         => (string)$row['number'],
            'type'           => (string)$row['type'],
            'label'          => (string)$row['label'],
            'customer_id'    => $row['customer_id'] !== null ? (int)$row['customer_id'] : null,
            'company_id'     => $row['company_id'] !== null ? (int)$row['company_id'] : null,
            'status'         => (string)$row['status'],
            'expires_at'     => $this->fmt_datetime($row['expires_at'] ?? null),
            'seats_total'    => $row['seats_total'] !== null ? (int)$row['seats_total'] : null,
            'sharing_mode'   => (string)$row['sharing_mode'],
            'renewal_of_id'  => $row['renewal_of_id'] !== null ? (int)$row['renewal_of_id'] : null,
            'owner_id'       => $row['owner_id'] !== null ? (int)$row['owner_id'] : null,
            // giữ nguyên ciphertext để Application tự decrypt bằng CryptoService
            'secret_primary'   => $row['secret_primary_encrypted'] ?? null,
            'secret_secondary' => $row['secret_secondary_encrypted'] ?? null,
            'username'         => $row['username'] ?? null,
            'extra_json'       => $row['extra_json_encrypted'] ?? null,
            'secret_mask'      => $row['secret_mask'] ?? null,
        ]);
    }

    private function dto_to_db(CredentialDTO $dto): array
    {
        return [
            'number'                    => $dto->number,
            'type'                      => $dto->type,
            'label'                     => $dto->label,
            'customer_id'               => $dto->customer_id,
            'company_id'                => $dto->company_id,
            'status'                    => $dto->status,
            'expires_at'                => $this->to_mysql_datetime($dto->expires_at),
            'seats_total'               => $dto->seats_total,
            'sharing_mode'              => $dto->sharing_mode,
            'renewal_of_id'             => $dto->renewal_of_id,
            'owner_id'                  => $dto->owner_id,
            'secret_primary_encrypted'  => $dto->secret_primary,
            'secret_secondary_encrypted'=> $dto->secret_secondary,
            'username'                  => $dto->username,
            'extra_json_encrypted'      => $dto->extra_json,
            'secret_mask'               => $dto->secret_mask,
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
