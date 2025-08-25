<?php

declare(strict_types=1);

namespace TMT\CRM\Infrastructure\Persistence;

use TMT\CRM\Domain\Repositories\UserRepositoryInterface;
use TMT\CRM\Infrastructure\Security\Capability;

/**
 * Lấy danh sách user qua API WordPress (get_users).
 * Lọc theo capability để chỉ hiện ai có quyền cập nhật khách hàng.
 */
final class WpdbUserRepository implements UserRepositoryInterface
{
    public function get_assignable_owners(): array
    {
        $users = get_users([
            'fields'  => ['ID', 'display_name', 'user_login'],
            'orderby' => 'display_name',
            'order'   => 'ASC',
        ]);

        $out = [];
        foreach ($users as $u) {
            // Chỉ ai có quyền cập nhật customer mới được chọn làm người phụ trách
            if (! user_can((int)$u->ID, Capability::CUSTOMER_CREATE)) {
                continue;
            }
            $label = $u->display_name ?: $u->user_login;
            $out[(int)$u->ID] = $label;
        }

        return $out;
    }
}
