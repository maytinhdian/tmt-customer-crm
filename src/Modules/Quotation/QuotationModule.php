<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Quotation;

use TMT\CRM\Modules\Quotation\Domain\Repositories\QuoteRepositoryInterface;
use TMT\CRM\Shared\Container\Container;

use TMT\CRM\Modules\Quotation\Presentation\Admin\Controller\QuoteController;
use TMT\CRM\Modules\Quotation\Application\Services\{QuoteService, NumberingService};
use TMT\CRM\Modules\Quotation\Domain\Repositories\QuoteQueryRepositoryInterface;
use TMT\CRM\Modules\Quotation\Infrastructure\Persistence\{
    WpdbQuoteRepository,
    WpdbQuoteQueryRepository,
    WpdbSequenceRepository
};

final class QuotationModule
{
    public static function register(): void
    {
        //---------------------
        // Bind theo Interface
        //---------------------
        Container::set(QuoteRepositoryInterface::class,       fn() => new WpdbQuoteRepository($GLOBALS['wpdb']));
        Container::set(QuoteQueryRepositoryInterface::class,       fn() => new WpdbQuoteQueryRepository($GLOBALS['wpdb']));
        Container::set('numbering', fn() => new NumberingService(Container::get('sequence-repo')));
        Container::set('sequence-repo', fn() => new WpdbSequenceRepository($GLOBALS['wpdb']));

        // Container wiring
        Container::set('quote-query-repo', fn() => Container::get(QuoteQueryRepositoryInterface::class));
        Container::set('quote-repo', fn() => Container::get(QuoteRepositoryInterface::class));

        Container::set('quote-service', fn() => new QuoteService(Container::get('quote-repo'), Container::get('numbering')));
        add_action('admin_init', function () {
            QuoteController::register();
        });
    }
}
