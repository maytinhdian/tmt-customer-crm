<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Events;

use TMT\CRM\Core\Events\Infrastructure\Providers\EventsServiceProvider;

final class EventsModule
{
    public const VERSION = '1.0.0';
    public const OPTION_VERSION = 'tmt_crm_core_events_version';

    /** Bootstrap (file chính) */
    public static function bootstrap(): void
    {
        EventsServiceProvider::register();
    }
}
