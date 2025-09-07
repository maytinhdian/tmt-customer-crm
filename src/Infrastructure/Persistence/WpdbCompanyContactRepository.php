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
        $title   = isset($arr['title']) ? (string)$arr['title'] : null;
        $start_date = isset($arr['start_date']) ? trim((string)$arr['start_date']) : '';
        $end_date   = isset($arr['end_date']) ? trim((string)$arr['end_date']) : '';
        $is_primary = !empty($arr['is_primary']) ? 1 : 0;
        $note       = isset($arr['note']) ? (string)$arr['note'] : null;
        $created_by = isset($arr['created_by']) ? (int)$arr['created_by'] : (int)get_current_user_id();

        $data = [
            'company_id' => $company_id,
            'customer_id' => $customer_id,
            'role'       => $role,
            'title' => $title,
            'is_primary' => $is_primary,
            'created_by' => $created_by,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];
        $format = ['%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s'];

        if ($title !== null) {
            $data['title'] = $title;
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

    /**
     * Đặt 1 contact làm liên hệ chính của company.
     * - Xác thực contact thuộc company.
     * - Reset tất cả is_primary = 0 cho company_id.
     * - Set is_primary = 1 cho customer_id chỉ định.
     *
     * @throws \RuntimeException khi dữ liệu không hợp lệ hoặc lỗi DB.
     */
    public function set_primary(int $company_id, int $customer_id): bool
    {
        $company_id = (int) $company_id;
        $customer_id = (int) $customer_id;

        // 1) Kiểm tra liên hệ có thuộc công ty không
        $exists = (int) $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(1) FROM {$this->t_contacts} WHERE customer_id = %d AND company_id = %d",
                $customer_id,
                $company_id
            )
        );
        if ($exists !== 1) {
            throw new \RuntimeException(__('Liên hệ không thuộc công ty hoặc không tồn tại.', 'tmt-crm'));
        }

        $now = current_time('mysql', true);

        // 2) Transaction (nếu DB hỗ trợ)
        $this->db->query('START TRANSACTION');

        try {
            // 2.1) Reset tất cả về 0
            $reset = $this->db->query(
                $this->db->prepare(
                    "UPDATE {$this->t_contacts}
                     SET is_primary = 0, updated_at = %s
                     WHERE company_id = %d",
                    $now,
                    $company_id
                )
            );
            if ($reset === false) {
                throw new \RuntimeException($this->db->last_error ?: __('Lỗi khi reset liên hệ chính.', 'tmt-crm'));
            }

            // 2.2) Set contact chỉ định về 1
            $set = $this->db->query(
                $this->db->prepare(
                    "UPDATE {$this->t_contacts}
                     SET is_primary = 1, updated_at = %s
                     WHERE customer_id = %d AND company_id = %d",
                    $now,
                    $customer_id,
                    $company_id
                )
            );

            if ($set === false || (int) $this->db->rows_affected < 1) {
                throw new \RuntimeException(__('Không thể đặt liên hệ làm chính.', 'tmt-crm'));
            }

            $this->db->query('COMMIT');
            return true;
        } catch (\Throwable $e) {
            $this->db->query('ROLLBACK');
            throw $e;
        }
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

    /**
     * Soft-detach: đặt end_date và bỏ cờ is_primary (giữ lịch sử).
     */
    public function detach(int $company_id, int $customer_id, ?string $end_date = null): bool
    {
        $company_id = (int) $company_id;
        $customer_id = (int) $customer_id;

        // 0) Kiểm tra tồn tại & thuộc công ty
        $exists = (int) $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(1) FROM {$this->t_contacts} WHERE customer_id = %d AND company_id = %d",
                $customer_id,
                $company_id
            )
        );
        if ($exists !== 1) {
            throw new \RuntimeException(__('Liên hệ không thuộc công ty hoặc không tồn tại.', 'tmt-crm'));
        }

        // 1) Chuẩn bị ngày kết thúc và thời điểm cập nhật
        $date = $end_date ?: wp_date('Y-m-d');            // theo timezone WP, dạng YYYY-MM-DD
        $now  = current_time('mysql', true);              // GMT datetime cho updated_at

        // 2) Transaction (nếu DB hỗ trợ)
        $this->db->query('START TRANSACTION');

        try {
            // 2.1) Đặt end_date + hạ is_primary về 0
            $sql = "UPDATE {$this->t_contacts}
                    SET end_date = %s,
                        is_primary = 0,
                        updated_at = %s
                    WHERE customer_id = %d AND company_id = %d";

            $ok = $this->db->query(
                $this->db->prepare($sql, $date, $now, $customer_id, $company_id)
            );

            if ($ok === false || (int) $this->db->rows_affected < 1) {
                throw new \RuntimeException(__('Không thể gỡ liên hệ.', 'tmt-crm'));
            }

            $this->db->query('COMMIT');
            return true;
        } catch (\Throwable $e) {
            $this->db->query('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Hard delete: xoá hẳn hàng khỏi bảng.
     * Chỉ dùng khi chắc chắn không cần lịch sử.
     */
    public function delete(int $customer_id): bool
    {
        $customer_id = (int) $customer_id;

        $ok = $this->db->delete($this->t_contacts, ['id' => $customer_id], ['%d']);
        if ($ok === false || (int) $this->db->rows_affected < 1) {
            throw new \RuntimeException(__('Không thể xoá liên hệ.', 'tmt-crm'));
        }
        return true;
    }

    /** @inheritDoc */
    public function find_paged_by_company(
        int $company_id,
        int $page,
        int $per_page,
        array $filters = [],
        array $sort = []
    ): array {
        $where  = ['company_id = %d'];
        $params = [$company_id];

        // Ví dụ filter
        if (!empty($filters['role'])) {
            $where[]  = 'role = %s';
            $params[] = (string)$filters['role'];
        }
        if (isset($filters['is_primary'])) {
            $where[]  = 'is_primary = %d';
            $params[] = (int)$filters['is_primary'];
        }
        if (!empty($filters['active_only'])) {
            $where[] = '(end_date IS NULL OR end_date >= CURDATE())';
        }

        $order_by = 'is_primary DESC, id DESC';
        if (!empty($sort['by'])) {
            $dir = (!empty($sort['dir']) && strtolower($sort['dir']) === 'asc') ? 'ASC' : 'DESC';
            // Chỉ cho phép sort trên các cột hợp lệ của bảng này
            $allowed = ['id', 'role', 'position', 'start_date', 'end_date', 'is_primary'];
            if (in_array($sort['by'], $allowed, true)) {
                $order_by = sprintf('%s %s, id DESC', $sort['by'], $dir);
            }
        }

        $offset = max(0, ($page - 1) * $per_page);

        $sql = "
            SELECT *
            FROM {$this->t_contacts}
            WHERE " . implode(' AND ', $where) . "
            ORDER BY {$order_by}
            LIMIT %d OFFSET %d
        ";

        $params[] = $per_page;
        $params[] = $offset;

        $rows = $this->db->get_results($this->db->prepare($sql, ...$params), ARRAY_A) ?: [];

        return array_map([$this, 'map_row_to_dto'], $rows);
    }

    public function count_by_company(int $company_id, array $filters = []): int
    {
        $where  = ['company_id = %d'];
        $params = [$company_id];

        if (!empty($filters['role'])) {
            $where[]  = 'role = %s';
            $params[] = (string)$filters['role'];
        }
        if (isset($filters['is_primary'])) {
            $where[]  = 'is_primary = %d';
            $params[] = (int)$filters['is_primary'];
        }
        if (!empty($filters['active_only'])) {
            $where[] = '(end_date IS NULL OR end_date >= CURDATE())';
        }

        $sql = "SELECT COUNT(1) FROM {$this->t_contacts} WHERE " . implode(' AND ', $where);
        return (int)$this->db->get_var($this->db->prepare($sql, ...$params));
    }


    /**
     * Lấy tên công ty theo ID. Nếu không có tên, trả về "#<id>".
     *
     * @param int $company_id
     * @return string
     */
    public function get_company_name(int $company_id): string
    {
        if ($company_id <= 0) {
            return '#0';
        }

        // Chuẩn bị câu lệnh có LIMIT 1 và chọn cột tường minh
        $sql = sprintf(
            'SELECT name FROM %s WHERE id = %%d LIMIT 1',
            $this->t_companies
        );

        $prepared = $this->db->prepare($sql, $company_id);
        if ($prepared === false) {
            return '#' . $company_id;
        }

        $name = $this->db->get_var($prepared);
        $name = is_string($name) ? trim($name) : '';

        return ($name !== '') ? $name : ('#' . $company_id);
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
