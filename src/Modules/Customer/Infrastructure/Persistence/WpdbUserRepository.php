<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Customer\Infrastructure\Persistence;

use wpdb;
use WP_User_Query;
use TMT\CRM\Modules\Customer\Application\DTO\UserDTO;
use TMT\CRM\Modules\Customer\Domain\Repositories\UserRepositoryInterface;

final class WpdbUserRepository implements UserRepositoryInterface
{
    private wpdb $db;
    private string $users_table;
    private string $usermeta_table;



    public function __construct(wpdb $db)
    {
        $this->db            = $db;
        $this->users_table   = $db->users;
        $this->usermeta_table = $db->usermeta;
    }

    public function search_for_select(
        string $keyword,
        int $page,
        int $per_page,
        string $must_capability
    ): array {
        $page      = max(1, $page);
        $limit     = max(1, $per_page);
        $fetch     = $limit + 1; // lấy dư 1 để biết còn trang sau
        $offset    = ($page - 1) * $limit;

        $args = [
            'number'  => $fetch,
            'offset'  => $offset,
            'search'  => $keyword !== '' ? '*' . $keyword . '*' : '*',
            'orderby' => 'display_name',
            'order'   => 'ASC',
            'fields'  => ['ID', 'display_name', 'user_email', 'user_login'],
        ];

        $q = new WP_User_Query($args);
        $users = (array) $q->get_results();

        // Lọc theo capability bắt buộc
        // Ghi chú: Dùng user_can($uid, $must_capability) để chỉ đưa user có quyền bắt buộc (ở đây sẽ là Capability::COMPANY_CREATE).
        $items = [];
        foreach ($users as $u) {
            if (!user_can($u->ID, $must_capability)) {
                continue;
            }
            $label = trim(($u->display_name ?: $u->user_login) . ' — ' . $u->user_email);
            $items[] = ['id' => (int)$u->ID, 'label' => $label];
        }

        // Nếu kết quả sau lọc > $limit thì cắt + more=true
        $more = false;
        if (count($items) > $limit) {
            array_pop($items);
            $more = true;
        }

        return ['items' => $items, 'more' => $more];
    }

    public function find_label_by_id(int $user_id): ?string
    {
        $u = get_user_by('ID', $user_id);
        if (!$u) return null;
        return trim(($u->display_name ?: $u->user_login) . ' — ' . $u->user_email);
    }
    public function get_display_name(int $user_id): ?string
    {
        $u = get_user_by('id', $user_id);
        if ($u instanceof \WP_User) {
            return $u->display_name !== '' ? $u->display_name : $u->user_login;
        }
        return null;
    }

    public function map_display_names(array $user_ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $user_ids)));
        if (!$ids) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $sql = "SELECT ID, display_name FROM {$this->db->users} WHERE ID IN ($placeholders)";
        $rows = $this->db->get_results($this->db->prepare($sql, ...$ids), ARRAY_A) ?: [];

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['ID']] = (string) $r['display_name'];
        }
        return $map;
    }


    /** @inheritDoc */
    public function find_by_ids(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $sql = "SELECT ID as id, display_name, user_email FROM {$this->users_table} WHERE ID IN ($placeholders)";
        $rows = $this->db->get_results($this->db->prepare($sql, ...$ids), ARRAY_A) ?: [];

        // Nếu cần lấy owner_phone từ usermeta
        $phones = [];
        $meta_key = 'owner_phone';
        $meta_sql = "
            SELECT user_id, meta_value
            FROM {$this->usermeta_table}
            WHERE meta_key = %s AND user_id IN ($placeholders)
        ";
        $meta_rows = $this->db->get_results(
            $this->db->prepare($meta_sql, $meta_key, ...$ids),
            ARRAY_A
        ) ?: [];
        foreach ($meta_rows as $m) {
            $phones[(int)$m['user_id']] = $m['meta_value'];
        }

        $map = [];
        foreach ($rows as $r) {
            $id = (int)$r['id'];
            $dto = new UserDTO(
                $id,
                $r['display_name'] ?? '',
                $r['user_email'] ?? null,
                $phones[$id] ?? null
            );
            $map[$id] = $dto;
        }
        return $map;
    }
}
