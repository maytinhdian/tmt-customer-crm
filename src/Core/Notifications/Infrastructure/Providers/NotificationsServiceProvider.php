<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Infrastructure\Providers;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Notifications\Application\Services\{DeliveryService, TemplateRenderer, NotificationDispatcher, PreferenceService};
use TMT\CRM\Core\Notifications\Infrastructure\Channels\{NoticeChannelAdapter, EmailChannelAdapter};

final class NotificationsServiceProvider
{
    public static function register(): void
    {
        Container::set(PreferenceService::class, static fn() => new PreferenceService());
        Container::set(TemplateRenderer::class, static fn() => new TemplateRenderer());

        // src/Core/Notifications/Infrastructure/Providers/NotificationsServiceProvider.php
        Container::set(NotificationDispatcher::class, static function () {
            return new \TMT\CRM\Core\Notifications\Application\Services\NotificationDispatcher(
                Container::get(\TMT\CRM\Core\Notifications\Application\Services\TemplateRenderer::class),
                Container::get(\TMT\CRM\Core\Notifications\Application\Services\DeliveryService::class),
                Container::get(\TMT\CRM\Core\Notifications\Application\Services\PreferenceService::class),
            );
        });

        // Channels map
        Container::set(\TMT\CRM\Core\Notifications\Application\Services\DeliveryService::class, static function () {
            return new \TMT\CRM\Core\Notifications\Application\Services\DeliveryService([
                'admin_notice' => new \TMT\CRM\Core\Notifications\Infrastructure\Channels\AdminNoticeDriver(),
                // 'email' => new EmailDriver(), // bật sau nếu cần
            ]);
        });
    }
}
