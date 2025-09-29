<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Numbering\Infrastructure\Providers;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Domain\Repositories\NumberingRepositoryInterface;
use TMT\CRM\Core\Numbering\Infrastructure\WpdbNumberingRepository;
use TMT\CRM\Core\Numbering\Domain\Services\NumberingService;

/**
 * Đăng ký các binding vào Container
 */
final class NumberingServiceProvider
{
    public static function register(): void
    {
        Container::set(NumberingRepositoryInterface::class, function () {
            global $wpdb;
            return new WpdbNumberingRepository($wpdb);
        });

        Container::set(NumberingService::class, function () {
            /** @var NumberingRepositoryInterface $repo */
            $repo = Container::get(NumberingRepositoryInterface::class);
            return new NumberingService($repo);
        });
    }
}
