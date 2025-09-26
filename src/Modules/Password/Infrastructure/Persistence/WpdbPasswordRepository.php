<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Password\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Modules\Password\Domain\Entities\PasswordItem;
use TMT\CRM\Domain\Repositories\PasswordRepositoryInterface;

final class WpdbPasswordRepository implements PasswordRepositoryInterface
{
    private string $table;

    public function __construct(private wpdb $db)
    {
        $this->table = $this->db->prefix . 'tmt_crm_passwords';
    }


    public function list(array $filters, int $page, int $per_page): array
    {
        $where  = 'WHERE 1=1';
        $params = [];

        if (!empty($filters['q'])) {
            $where .= " AND (title LIKE %s OR username LIKE %s OR url LIKE %s)";
            $like = '%' . $this->db->esc_like($filters['q']) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['subject']) && in_array($filters['subject'], ['company', 'customer'], true)) {
            $where .= " AND subject = %s";
            $params[] = $filters['subject'];
        }
        if (!empty($filters['category'])) {
            $where .= " AND category = %s";
            $params[] = (string)$filters['category'];
        }
        if (!empty($filters['company_id'])) {
            $where .= " AND company_id = %d";
            $params[] = (int)$filters['company_id'];
        }
        if (!empty($filters['customer_id'])) {
            $where .= " AND customer_id = %d";
            $params[] = (int)$filters['customer_id'];
        }
        if (isset($filters['deleted']) && $filters['deleted'] === 'only') {
            $where .= " AND deleted_at IS NOT NULL";
        } elseif (isset($filters['deleted']) && $filters['deleted'] === 'exclude') {
            $where .= " AND deleted_at IS NULL";
        }

        $offset   = max(0, ($page - 1) * $per_page);

        // --- ITEMS
        $sql_items = "SELECT * FROM {$this->table} {$where} ORDER BY id DESC LIMIT %d OFFSET %d";
        $items_params = array_merge($params, [(int)$per_page, (int)$offset]);

        // wpdb->prepare nhận 1 mảng tham số
        $prepared_items_sql = $this->db->prepare($sql_items, $items_params);
        $rows = $this->db->get_results($prepared_items_sql, ARRAY_A) ?: [];

        // --- COUNT
        $sql_count = "SELECT COUNT(1) FROM {$this->table} {$where}";
        $prepared_count_sql = !empty($params)
            ? $this->db->prepare($sql_count, $params)
            : $sql_count; // không có placeholder thì dùng thẳng

        $total = (int)$this->db->get_var($prepared_count_sql);

        $entities = array_map(function (array $row) {
            return new PasswordItem(
                id: (int)$row['id'],
                title: (string)$row['title'],
                username: $row['username'] ?: null,
                ciphertext: (string)$row['ciphertext'],
                nonce: (string)$row['nonce'],
                url: $row['url'] ?: null,
                notes: $row['notes'] ?: null,
                owner_id: (int)$row['owner_id'],
                company_id: $row['company_id'] ? (int)$row['company_id'] : null,
                customer_id: $row['customer_id'] ? (int)$row['customer_id'] : null,
                subject: $row['subject'] ? (string)$row['subject'] : null,
                category: $row['category'] ? (string)$row['category'] : null,
                created_at: (string)$row['created_at'],
                updated_at: (string)$row['updated_at'],
                deleted_at: $row['deleted_at'] ?: null,
            );
        }, $rows);

        return ['items' => $entities, 'total' => $total];
    }

    public function find(int $id): ?PasswordItem
    {
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->table} WHERE id=%d", $id), ARRAY_A);
        if (!$row) return null;
        return new PasswordItem(
            id: (int)$row['id'],
            title: (string)$row['title'],
            username: $row['username'] ?: null,
            ciphertext: (string)$row['ciphertext'],
            nonce: (string)$row['nonce'],
            url: $row['url'] ?: null,
            notes: $row['notes'] ?: null,
            owner_id: (int)$row['owner_id'],
            company_id: $row['company_id'] ? (int)$row['company_id'] : null,
            customer_id: $row['customer_id'] ? (int)$row['customer_id'] : null,
            subject: $row['subject'] ? (string)$row['subject'] : null,
            category: $row['category'] ? (string)$row['category'] : null,
            created_at: (string)$row['created_at'],
            updated_at: (string)$row['updated_at'],
            deleted_at: $row['deleted_at'] ?: null,
        );
    }

    public function insert(PasswordItem $e): int
    {
        $this->db->insert($this->table, [
            'title'       => $e->title,
            'username'    => $e->username,
            'ciphertext'  => $e->ciphertext,
            'nonce'       => $e->nonce,
            'url'         => $e->url,
            'notes'       => $e->notes,
            'owner_id'    => $e->owner_id,
            'company_id'  => $e->company_id,
            'customer_id' => $e->customer_id,
            'subject'     => $e->subject,
            'category'    => $e->category,
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql'),
            'deleted_at'  => null,
        ], ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s']);
        return (int)$this->db->insert_id;
    }

    public function update(PasswordItem $e): bool
    {
        $r = $this->db->update($this->table, [
            'title'       => $e->title,
            'username'    => $e->username,
            'ciphertext'  => $e->ciphertext,
            'nonce'       => $e->nonce,
            'url'         => $e->url,
            'notes'       => $e->notes,
            'company_id'  => $e->company_id,
            'customer_id' => $e->customer_id,
            'subject'     => $e->subject,
            'category'    => $e->category,
            'updated_at'  => current_time('mysql'),
        ], ['id' => $e->id], ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s'], ['%d']);
        return $r !== false;
    }

    public function soft_delete(int $id, int $deleted_by, ?string $reason = null): bool
    {
        // có thể mở rộng ghi log xóa ở bảng audit
        $r = $this->db->update($this->table, [
            'deleted_at' => current_time('mysql'),
        ], ['id' => $id], ['%s'], ['%d']);
        return $r !== false;
    }

    public function restore(int $id): bool
    {
        $r = $this->db->update($this->table, ['deleted_at' => null], ['id' => $id], ['%s'], ['%d']);
        return $r !== false;
    }
}
