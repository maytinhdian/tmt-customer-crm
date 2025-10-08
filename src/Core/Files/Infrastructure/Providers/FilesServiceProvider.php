<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Files\Infrastructure\Providers;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Files\Application\Services\FileService;
use TMT\CRM\Core\Files\Domain\Contracts\StorageInterface;
use TMT\CRM\Core\Files\Application\Storage\WpUploadsStorage; // ✅ sửa namespace
use TMT\CRM\Core\Files\Domain\Repositories\FileRepositoryInterface;
use TMT\CRM\Core\Files\Infrastructure\Persistence\WpdbFileRepository;
use TMT\CRM\Shared\Logging\LoggerInterface;
use TMT\CRM\Core\Events\Domain\Contracts\EventBusInterface;

final class FilesServiceProvider
{
    public static function register(): void
    {
        // Storage driver
        Container::set(StorageInterface::class, function () {
            return new WpUploadsStorage();
        });

        // Repository
        Container::set(FileRepositoryInterface::class, function () {
            global $wpdb;
            return new WpdbFileRepository($wpdb);
        });

        // FileService (KHÔNG nhận $c; lấy deps qua Container::get)
        Container::set(FileService::class, function () {
            /** @var StorageInterface $storage */
            $storage = Container::get(StorageInterface::class);
            /** @var FileRepositoryInterface $repo */
            $repo    = Container::get(FileRepositoryInterface::class);
            /** @var LoggerInterface $logger */
            $logger  = Container::get(LoggerInterface::class);
            /** @var EventBusInterface $events */
            $events  = Container::get(EventBusInterface::class);

            return new FileService($storage, $repo, $logger, $events);
        });
    }
}
