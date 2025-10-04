<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Accounts\Application\Services;

use TMT\CRM\Domain\Repositories\UserRepositoryInterface;

final class PreferenceService
{
    public function __construct(private UserRepositoryInterface $users) {}

    public function get(string $key, ?int $user_id = null, mixed $default = null): mixed
    {
        $uid = $user_id ?? get_current_user_id();
        $val = get_user_meta($uid, "tmt_pref_{$key}", true);
        return $val === '' ? $default : $val;
    }

    public function set(string $key, mixed $value, ?int $user_id = null): void
    {
        $uid = $user_id ?? get_current_user_id();
        update_user_meta($uid, "tmt_pref_{$key}", $value);
    }
}
