<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Accounts\Infrastructure\Providers;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Domain\Repositories\UserRepositoryInterface;
use TMT\CRM\Core\Accounts\Infrastructure\Persistence\WpdbUserRepository;
use TMT\CRM\Core\Accounts\Application\Services\{PreferenceService, UserService};

final class AccountsServiceProvider
{
    public static function register(): void
    {
        Container::set(UserRepositoryInterface::class, fn() => new WpdbUserRepository($GLOBALS['wpdb']));
        Container::set('user-repo', fn() => Container::get(UserRepositoryInterface::class));

        Container::set(PreferenceService::class, fn() => new PreferenceService(Container::get(UserRepositoryInterface::class)));
        Container::set(UserService::class, fn() => new UserService(Container::get(UserRepositoryInterface::class)));
    }
}
