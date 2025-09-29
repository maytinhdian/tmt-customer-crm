<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Infrastructure\Providers;

use TMT\CRM\Shared\Container\Container;

use TMT\CRM\Domain\Repositories\CredentialRepositoryInterface;
use TMT\CRM\Domain\Repositories\CredentialSeatAllocationRepositoryInterface;
use TMT\CRM\Domain\Repositories\CredentialActivationRepositoryInterface;
use TMT\CRM\Domain\Repositories\CredentialDeliveryRepositoryInterface;

use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialRepository;
use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialSeatAllocationRepository;
use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialActivationRepository;
use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialDeliveryRepository;

use TMT\CRM\Modules\License\Application\Services\CryptoService;
use TMT\CRM\Modules\License\Application\Services\PolicyService;
use TMT\CRM\Modules\License\Application\Services\CredentialService;
use TMT\CRM\Modules\License\Application\Services\AllocationService;
use TMT\CRM\Modules\License\Application\Services\ActivationService;
use TMT\CRM\Modules\License\Application\Services\DeliveryService;
use TMT\CRM\Modules\License\Application\Services\ReminderService;

/**
 * LicenseServiceProvider
 * - Cố gắng set vào nhiều kiểu Container thường gặp trong dự án WP/CRM.
 * - Nếu không tìm thấy container, không làm gì (Presentation vẫn chạy với new trực tiếp).
 */
final class LicenseServiceProvider
{
    public static function register(): void
    {
        // 1) Repositories 
        Container::set(CredentialRepositoryInterface::class, fn() => new WpdbCredentialRepository($GLOBALS['wpdb']));
        Container::set(CredentialSeatAllocationRepositoryInterface::class, fn() => new WpdbCredentialSeatAllocationRepository($GLOBALS['wpdb']));
        Container::set(CredentialActivationRepositoryInterface::class, fn() => new WpdbCredentialActivationRepository($GLOBALS['wpdb']));
        Container::set(CredentialDeliveryRepositoryInterface::class, fn() => new WpdbCredentialDeliveryRepository($GLOBALS['wpdb']));

        // 2) Services 
        Container::set(CryptoService::class, fn() => new CryptoService());
        Container::set(PolicyService::class, fn() => new PolicyService());
        Container::set(CredentialService::class, fn() => new CredentialService(
            Container::get(CredentialRepositoryInterface::class),
            Container::get(CredentialSeatAllocationRepositoryInterface::class),
            Container::get(CredentialActivationRepositoryInterface::class),
            Container::get(CryptoService::class)
        ));
        Container::set(AllocationService::class, fn() => new AllocationService(
            Container::get(CredentialRepositoryInterface::class),
            Container::get(CredentialSeatAllocationRepositoryInterface::class),
            Container::get(CredentialActivationRepositoryInterface::class),
            Container::get(PolicyService::class)
        ));
        Container::set(ActivationService::class, fn() => new ActivationService(
            Container::get(CredentialRepositoryInterface::class),
            Container::get(CredentialSeatAllocationRepositoryInterface::class),
            Container::get(CredentialActivationRepositoryInterface::class),
            Container::get(PolicyService::class),
        ));
        Container::set(DeliveryService::class, fn() => new DeliveryService(
            Container::get(CredentialDeliveryRepositoryInterface::class)
        ));
        Container::set(ReminderService::class, fn() => new ReminderService(
            Container::get(CredentialRepositoryInterface::class),
        ));
    }
}
