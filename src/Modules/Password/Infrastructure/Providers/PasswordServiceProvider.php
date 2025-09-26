<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Password\Infrastructure\Providers;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Domain\Repositories\PasswordRepositoryInterface;
use TMT\CRM\Modules\Password\Infrastructure\Persistence\WpdbPasswordRepository;
use TMT\CRM\Modules\Password\Application\Services\{PasswordService, CryptoService, PolicyService};

final class PasswordServiceProvider
{
    public static function register(): void
    {
        Container::set(PasswordRepositoryInterface::class, fn() => new WpdbPasswordRepository($GLOBALS['wpdb']));



        Container::set(CryptoService::class, fn() => new CryptoService());

        Container::set(PolicyService::class, fn() => new PolicyService());

        Container::set(PasswordService::class, fn() => new PasswordService(
            Container::get(PasswordRepositoryInterface::class),
            Container::get(CryptoService::class),
            Container::get(PolicyService::class)
        ));
    }
}
