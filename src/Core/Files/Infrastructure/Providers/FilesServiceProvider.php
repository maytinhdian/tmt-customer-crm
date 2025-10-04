<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Files\Infrastructure\Providers;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Files\Application\Services\Storage\{StorageInterface, WpUploadsStorage};
use TMT\CRM\Domain\Repositories\FileRepositoryInterface;
use TMT\CRM\Core\Files\Infrastructure\Persistence\WpdbFileRepository;
use TMT\CRM\Core\Files\Application\Services\PolicyService as FilesPolicyService;
use TMT\CRM\Domain\Repositories\CapabilitiesRepositoryInterface;

final class FilesServiceProvider
{
    public static function register(): void
    {
        // Storage mặc định → wp-uploads
        Container::set(StorageInterface::class, static fn() => new WpUploadsStorage());

        // Bind repository nếu class tồn tại
        if (interface_exists(FileRepositoryInterface::class) && class_exists(WpdbFileRepository::class)) {
            Container::set(FileRepositoryInterface::class, static function () {
                global $wpdb;
                return new WpdbFileRepository($wpdb);
            });
        }
        Container::set(FilesPolicyService::class, static function () {
            /** @var CapabilitiesRepositoryInterface $repo */
            $repo = Container::get(CapabilitiesRepositoryInterface::class);
            return new FilesPolicyService($repo);
        });
    }
}
