<?php

use TMT\CRM\Core\Notifications\Application\Services\{
    NotificationDispatcher,
    TemplateRenderer,
    PreferenceService,
    DeliveryService
};
use TMT\CRM\Domain\Repositories\{
    NotificationRepositoryInterface,
    DeliveryRepositoryInterface,
    TemplateRepositoryInterface,
    PreferenceRepositoryInterface
};
use TMT\CRM\Core\Notifications\Infrastructure\Repositories\{
    DbNotificationRepository,
    DbDeliveryRepository,
    DbTemplateRepository,
    DbPreferenceRepository
};
use TMT\Crm\Shared\Container\Container;

add_action('plugins_loaded', function () {
    global $wpdb;

    // Repositories
    Container::set(NotificationRepositoryInterface::class, fn() => new DbNotificationRepository($wpdb));
    Container::set(DeliveryRepositoryInterface::class,     fn() => new DbDeliveryRepository($wpdb));
    Container::set(TemplateRepositoryInterface::class,     fn() => new DbTemplateRepository($wpdb));
    Container::set(PreferenceRepositoryInterface::class,   fn() => new DbPreferenceRepository($wpdb));

    // Services (class thật)
    Container::set(TemplateRenderer::class, fn() => new TemplateRenderer());
    Container::set(PreferenceService::class, fn() => new PreferenceService(
        Container::get(PreferenceRepositoryInterface::class)
    ));
    Container::set(DeliveryService::class, fn() => new DeliveryService(
        Container::get(DeliveryRepositoryInterface::class)
    ));

    // Dispatcher
    Container::set(NotificationDispatcher::class, fn() => new NotificationDispatcher(
        Container::get(NotificationRepositoryInterface::class),
        Container::get(DeliveryRepositoryInterface::class),
        Container::get(TemplateRepositoryInterface::class),
        Container::get(PreferenceService::class),
        Container::get(TemplateRenderer::class),
        Container::get(DeliveryService::class),
    ));
});
