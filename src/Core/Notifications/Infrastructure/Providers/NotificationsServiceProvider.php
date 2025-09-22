<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Infrastructure\Providers;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Shared\EventBus\EventBus;

use TMT\CRM\Core\Notifications\Application\Services\DeliveryService;
use TMT\CRM\Core\Notifications\Application\Services\TemplateRenderer;
use TMT\CRM\Core\Notifications\Application\Services\NotificationDispatcher;
use TMT\CRM\Core\Notifications\Application\Services\PreferenceService;

use TMT\CRM\Core\Notifications\Infrastructure\Channels\NoticeChannelAdapter;
use TMT\CRM\Core\Notifications\Infrastructure\Channels\EmailChannelAdapter;

final class NotificationsServiceProvider
{
    public static function register(): void
    {
        // Adapters
        Container::set(NoticeChannelAdapter::class, static fn() => new NoticeChannelAdapter());
        Container::set(EmailChannelAdapter::class, static fn() => new EmailChannelAdapter());

        // Map kênh
        Container::set('notifications.channels', static function (): array {
            $channels = [
                'notice' => Container::get(NoticeChannelAdapter::class),
                'email'  => Container::get(EmailChannelAdapter::class),
            ];
            return apply_filters('tmt_crm_notifications_channels', $channels);
        });

        // Services
        Container::set(TemplateRenderer::class, static fn() => new TemplateRenderer());
        Container::set(PreferenceService::class, static fn() => new PreferenceService());

        Container::set(DeliveryService::class, static function (): DeliveryService {
            return new DeliveryService(
                channels: Container::get('notifications.channels')
                // P1 sẽ ghép thêm repo lưu DB nếu cần
            );
        });

        Container::set(NotificationDispatcher::class, static function (): NotificationDispatcher {
            return new NotificationDispatcher(
                renderer: Container::get(TemplateRenderer::class),
                delivery: Container::get(DeliveryService::class),
                preferences: Container::get(PreferenceService::class),
            );
        });

        // Subscribe event mẫu (đổi theo event thực tế trong dự án)
        EventBus::subscribe('CompanyCreated', [Container::get(NotificationDispatcher::class), 'handle']);
        EventBus::subscribe('CompanySoftDeleted', [Container::get(NotificationDispatcher::class), 'handle']);
        EventBus::subscribe('CompanyRestore', [Container::get(NotificationDispatcher::class), 'handle']);
    }
}
