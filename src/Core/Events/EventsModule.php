<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Events;

use TMT\CRM\Core\Events\Infrastructure\Providers\EventsServiceProvider;
use TMT\CRM\Core\Events\Infrastructure\Providers\EventStoreServiceProvider;
final class EventsModule
{
    public const VERSION = '1.0.0';
    public const OPTION_VERSION = 'tmt_crm_core_events_version';

    /** Bootstrap (file chính) */
    public static function boot(): void
    {
        EventsServiceProvider::register();
        EventStoreServiceProvider::register();
    }
}
