<?php

declare(strict_types=1);

namespace TMT\CRM\Infrastructure\Users;

use WP_User_Query;
use TMT\CRM\Domain\Repositories\UserRepositoryInterface;

final class WpdbUserRepository implements UserRepositoryInterface
{
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
}
