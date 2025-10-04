<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Accounts\Application\Services;

final class AccountPolicyService
{
    public static function can_use_picker(): bool
    {
        return current_user_can('list_users');
    }
}
