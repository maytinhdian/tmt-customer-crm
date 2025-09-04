<?php

declare(strict_types=1);

namespace TMT\CRM\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Application\DTO\CompanyContactDTO;
use TMT\CRM\Application\DTO\CompanyContactViewDTO;
use TMT\CRM\Domain\Repositories\CompanyContactRepositoryInterface;

final class WpdbCompanyContactRepository implements CompanyContactRepositoryInterface
{
    private wpdb $db;

    private string $t_contacts;
    private string $t_customers;
    private string $t_companies;
    private string $t_users;

    public function __construct(wpdb $db)
    {
        $this->db    = $db;
        $this->t_contacts = ($db->prefix . 'tmt_crm_company_contacts');
        $this->t_customers = ($db->prefix . 'tmt_crm_customers');
        $this->t_companies = ($db->prefix . 'tmt_crm_companies');
        $this->t_users = ($db->prefix) . 'users';
    }

    public function find_active_contacts_by_company(int $company_id, ?string $role = null): array
    {
        $where  = ["company_id = %d", "(end_date IS NULL OR end_date >= CURDATE())"];
        $params = [$company_id];

        if ($role !== null && $role !== '') {
            $where[]  = "role = %s";
            $params[] = $role;
        }

        $sql = "SELECT * FROM {$this->t_contacts} WHERE " . implode(' AND ', $where) . " ORDER BY is_primary DESC, id DESC";
        $rows = $this->db->get_results($this->db->prepare($sql, ...$params), ARRAY_A);

        return array_map([$this, 'map_row_to_dto'], $rows ?: []);
    }

    public function attach_customer(CompanyContactDTO $dto): int
    {
        // Lấy mảng an toàn từ DTO (DTO của bạn có trait AsArrayTrait → to_array()).
        $arr = method_exists($dto, 'to_array') ? $dto->to_array() : [];

        // Bắt buộc
        $company_id  = (int)($arr['company_id'] ?? 0);
        $customer_id = (int)($arr['customer_id'] ?? 0);

        // Tuỳ chọn
        $role       = isset($arr['role']) ? (string)$arr['role'] : '';
        $position   = isset($arr['position']) ? (string)$arr['position'] : null;
        $start_date = isset($arr['start_date']) ? trim((string)$arr['start_date']) : '';
        $end_date   = isset($arr['end_date']) ? trim((string)$arr['end_date']) : '';
        $is_primary = !empty($arr['is_primary']) ? 1 : 0;
        $note       = isset($arr['note']) ? (string)$arr['note'] : null;
        $created_by = isset($arr['created_by']) ? (int)$arr['created_by'] : (int)get_current_user_id();

        $data = [
            'company_id' => $company_id,
            'customer_id' => $customer_id,
            'role'       => $role,
            'is_primary' => $is_primary,
            'created_by' => $created_by,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];
        $format = ['%d', '%d', '%s', '%d', '%d', '%s', '%s'];

        if ($position !== null) {
            $data['position'] = $position;
            $format[] = '%s';
        }
        if ($note     !== null) {
            $data['note']     = $note;
            $format[] = '%s';
        }
        if ($start_date !== '') {
            $data['start_date'] = $start_date;
            $format[] = '%s';
        }
        if ($end_date   !== '') {
            $data['end_date']   = $end_date;
            $format[] = '%s';
        }

        $ok = $this->db->insert($this->t_contacts, $data, $format);
        if ($ok === false) {
            throw new \RuntimeException('DB error (attach_customer): ' . $this->db->last_error);
        }
        return (int)$this->db->insert_id;
    }

    public function is_customer_active_in_company(int $company_id, int $customer_id): bool
    {
        $sql = "
            SELECT 1
            FROM {$this->t_contacts}
            WHERE company_id = %d
              AND customer_id = %d
              AND (end_date IS NULL OR end_date >= CURDATE())
            LIMIT 1
        ";
        $val = $this->db->get_var($this->db->prepare($sql, $company_id, $customer_id));
        return $val === '1' || $val === 1;
    }

    public function unset_primary(int $company_id): void
    {
        $res = $this->db->update(
            $this->t_contacts,
            ['is_primary' => 0],
            ['company_id' => $company_id],
            ['%d'],
            ['%d']
        );
        if ($res === false) {
            throw new \RuntimeException('DB error (unset_primary): ' . $this->db->last_error);
        }
    }

    public function find_paged_with_relations(
        int $company_id,
        int $page,
        int $per_page,
        array $filters = []
    ): array {
        $offset = max(0, ($page - 1) * $per_page);

        $where  = ["cc.company_id = %d"];
        $params = [$company_id];

        // keyword tìm theo tên/điện thoại/email của customer
        if (!empty($filters['keyword'])) {
            $kw = '%' . $this->db->esc_like((string)$filters['keyword']) . '%';
            $where[]  = "(c.full_name LIKE %s OR c.phone LIKE %s OR c.email LIKE %s)";
            array_push($params, $kw, $kw, $kw);
        }

        // còn hiệu lực?
        if (!empty($filters['active_only'])) {
            $where[] = "(cc.end_date IS NULL OR cc.end_date >= CURDATE())";
        }

        $orderby = in_array(($filters['orderby'] ?? 'id'), ['id', 'role', 'is_primary', 'start_date', 'end_date'], true)
            ? $filters['orderby'] : 'id';
        $order   = strtoupper($filters['order'] ?? 'DESC');
        if (!in_array($order, ['ASC', 'DESC'], true)) $order = 'DESC';

        $sql = "
            SELECT
                cc.id,
                cc.company_id,
                cc.customer_id,
                cc.role,
                cc.title,
                cc.is_primary,
                cc.start_date,
                cc.end_date,

                c.full_name   AS customer_full_name,
                c.phone       AS customer_phone,
                c.email       AS customer_email,

                co.owner_id   AS owner_id,
                u.display_name AS owner_name
            FROM {$this->t_contacts} AS cc
            LEFT JOIN {$this->t_customers} AS c ON c.id = cc.customer_id
            LEFT JOIN {$this->t_companies} AS co ON co.id = cc.company_id
            LEFT JOIN {$this->t_users}    AS u  ON u.ID = co.owner_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY cc.{$orderby} {$order}
            LIMIT %d OFFSET %d
        ";

        $rows = $this->db->get_results(
            $this->db->prepare($sql, ...array_merge($params, [$per_page, $offset])),
            ARRAY_A
        ) ?: [];

        return array_map(function (array $r): CompanyContactViewDTO {
            return new CompanyContactViewDTO(
                (int)$r['id'],
                (int)$r['company_id'],
                (int)$r['customer_id'],
                (string)($r['role'] ?? ''),
                $r['title'] !== null ? (string)$r['title'] : null,
                (bool)$r['is_primary'],
                $r['start_date'] ?? null,
                $r['end_date'] ?? null,
                $r['customer_full_name'] ?? null,
                $r['customer_phone'] ?? null,
                $r['customer_email'] ?? null,
                isset($r['owner_id']) ? (int)$r['owner_id'] : null,
                $r['owner_name'] ?? null
            );
        }, $rows);
    }

    public function count_by_company_with_filters(int $company_id, array $filters = []): int
    {
        $where  = ["cc.company_id = %d"];
        $params = [$company_id];

        if (!empty($filters['keyword'])) {
            $kw = '%' . $this->db->esc_like((string)$filters['keyword']) . '%';
            $where[]  = "(c.full_name LIKE %s OR c.phone LIKE %s OR c.email LIKE %s)";
            array_push($params, $kw, $kw, $kw);
        }
        if (!empty($filters['active_only'])) {
            $where[] = "(cc.end_date IS NULL OR cc.end_date >= CURDATE())";
        }

        $sql = "
            SELECT COUNT(*)
            FROM {$this->t_contacts} AS cc
            LEFT JOIN {$this->t_customers} AS c ON c.id = cc.customer_id
            WHERE " . implode(' AND ', $where);

        return (int)$this->db->get_var($this->db->prepare($sql, ...$params));
    }

    /** Helper map */
    private function map_row_to_dto(array $row): CompanyContactDTO
    {
        return new CompanyContactDTO(
            (int)$row['id'],
            (int)$row['company_id'],
            (int)$row['customer_id'],
            (string)$row['role'],
            $row['title'] ?? null,
            (bool)$row['is_primary'],
            $row['start_date'] ?? null,
            $row['end_date'] ?? null,
            $row['note'] ?? null,
            (int)$row['created_by'] ?? null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
