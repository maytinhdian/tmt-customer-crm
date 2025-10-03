<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Files\Infrastructure\Providers;

use TMT\CRM\Domain\Repositories\FileRepositoryInterface;
use TMT\CRM\Core\Files\Infrastructure\Persistence\WpdbFileRepository;
use TMT\CRM\Core\Files\Application\Services\FileService;
use TMT\CRM\Core\Files\Application\Services\PolicyService;
use TMT\CRM\Core\Files\Application\Services\Storage\WpUploadsStorage;
use TMT\CRM\Core\Files\Application\Services\Storage\StorageInterface;
use TMT\CRM\Shared\Container\Container;

final class FilesServiceProvider
{
    public static function register(): void
    {
        Container::set(FileRepositoryInterface::class, fn() => new WpdbFileRepository($GLOBALS['wpdb']));
        Container::set(StorageInterface::class, fn() => new WpUploadsStorage());
        Container::set(FileService::class, fn() => new FileService(
            Container::get(FileRepositoryInterface::class),
            new PolicyService(),
            Container::get(StorageInterface::class)
        ));
    }
}
