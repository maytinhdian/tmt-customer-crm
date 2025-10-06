<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Log\Infrastructure\Providers;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Events\Infrastructure\Providers\SubscriberLoader;
use TMT\CRM\Core\Log\Presentation\Subscribers\CompanyLogSubscriber;
use TMT\CRM\Shared\Logging\ChannelLoggerFactory;
use TMT\CRM\Shared\Logging\LogLevel;

/**
 * Đăng ký DI và nạp subscriber vào EventBus.
 */
final class LogServiceProvider
{
    public static function register(): void
    {
        // Bind CompanyLogSubscriber với Logger channel "events" (ghi ra file + DB)
        Container::set(CompanyLogSubscriber::class, static function (Container $c) {
            // Logger: channel = 'events', min_level = info, targets = both (file + db)
            $logger = ChannelLoggerFactory::make('events', LogLevel::INFO, 'both');
            return new CompanyLogSubscriber($logger);
        });

        // Nạp subscriber vào EventBus
        SubscriberLoader::registerMany([
            CompanyLogSubscriber::class,
        ]);
    }
}
