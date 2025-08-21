<?php

namespace TMT\CRM\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Application\DTO\CompanyDTO;
use TMT\CRM\Domain\Entities\Company;
use TMT\CRM\Domain\Repositories\CompanyRepositoryInterface;

class WpdbCompanyRepository implements CompanyRepositoryInterface
{
    private wpdb $db;
    private string $table;

    public function __construct(wpdb $db)
    {
        $this->db = $db;
        $this->table = $this->db->prefix . 'tmt_crm_companies';
    }

    public function install(): void
    {
        $charset = $this->db->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `tax_code` VARCHAR(64) NULL,
            `phone` VARCHAR(32) NULL,
            `email` VARCHAR(190) NULL,
            `website` VARCHAR(255) NULL,
            `address` TEXT NULL,
            `note` TEXT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_name` (`name`),
            KEY `idx_phone` (`phone`),
            KEY `idx_email` (`email`),
            UNIQUE KEY `uniq_tax_code` (`tax_code`)
        ) $charset;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function find_by_id(int $id): ?Company
    {
        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id),
            ARRAY_A
        );
        return $row ? $this->row_to_entity($row) : null;
    }

    public function search(string $keyword = '', int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($keyword !== '') {
            $like = '%' . $this->db->esc_like($keyword) . '%';
            $where .= " AND (name LIKE %s OR phone LIKE %s OR email LIKE %s OR tax_code LIKE %s)";
            $params = [$like, $like, $like, $like];
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->table} WHERE $where ORDER BY updated_at DESC, id DESC LIMIT %d OFFSET %d";
        $params = array_merge($params, [$perPage, $offset]);
        // ✅ LUÔN dùng placeholder với prepare:
        $items = array_map([$this, 'row_to_entity'], $this->db->get_results($this->db->prepare($sql, ...$params), ARRAY_A) ?: []);
        $total = (int)$this->db->get_var("SELECT FOUND_ROWS()");
        return ['items' => $items, 'total' => $total];
    }

    public function insert(CompanyDTO $dto): int
    {
        $ok = $this->db->insert($this->table, [
            'name'      => $dto->name,
            'tax_code'  => $dto->taxCode,
            'phone'     => $dto->phone,
            'email'     => $dto->email,
            'website'   => $dto->website,
            'address'   => $dto->address,
            'note'      => $dto->note,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ], ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);
        return $ok ? (int)$this->db->insert_id : 0;
    }

    public function update(CompanyDTO $dto): bool
    {
        if (!$dto->id) return false;
        $ok = $this->db->update($this->table, [
            'name'      => $dto->name,
            'tax_code'  => $dto->taxCode,
            'phone'     => $dto->phone,
            'email'     => $dto->email,
            'website'   => $dto->website,
            'address'   => $dto->address,
            'note'      => $dto->note,
            'updated_at' => current_time('mysql'),
        ], ['id' => $dto->id], ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'], ['%d']);
        return (bool)$ok;
    }

    public function delete(int $id): bool
    {
        return (bool)$this->db->delete($this->table, ['id' => $id], ['%d']);
    }

    public function find_duplicate(?int $excludeId, ?string $name, ?string $taxCode, ?string $phone, ?string $email): ?Company
    {
        $conds = [];
        $args  = [];
        if ($taxCode) {
            $conds[] = "tax_code = %s";
            $args[] = $taxCode;
        }
        if ($phone) {
            $conds[] = "phone = %s";
            $args[] = $phone;
        }
        if ($email) {
            $conds[] = "email = %s";
            $args[] = $email;
        }
        if ($name) {
            $conds[] = "name = %s";
            $args[] = $name;
        }
        if (!$conds) return null;

        $sql = "SELECT * FROM {$this->table} WHERE (" . implode(' OR ', $conds) . ")";
        if ($excludeId) {
            $sql .= " AND id <> %d";
            $args[] = $excludeId;
        }

        $row = $this->db->get_row($this->db->prepare($sql, ...$args), ARRAY_A);
        return $row ? $this->row_to_entity($row) : null;
    }

    private function row_to_entity(array $r): Company
    {
        return new Company(
            (int)$r['id'],
            (string)$r['name'],
            $r['tax_code'] ?: null,
            $r['phone'] ?: null,
            $r['email'] ?: null,
            $r['website'] ?: null,
            $r['address'] ?: null,
            $r['note'] ?: null,
            (string)$r['created_at'],
            (string)$r['updated_at']
        );
    }
}
