<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Quotation;

use TMT\CRM\Modules\Quotation\Domain\Repositories\QuoteRepositoryInterface;
use TMT\CRM\Shared\Container;

use TMT\CRM\Modules\Quotation\Presentation\Admin\Controller\CustomerController;
use TMT\CRM\Modules\Quotation\Application\Services\{QuoteService, NumberingService};
use TMT\CRM\Modules\Quotation\Infrastructure\Persistence\{
    WpdbQuoteRepository,
    WpdbSequenceRepository
};

final class QuotationModule{
    public static function register(): void
    {
        //---------------------
        // Bind theo Interface
        //---------------------
        Container::set(QuoteRepositoryInterface::class,       fn() => new WpdbQuoteRepository($GLOBALS['wpdb']));
        // Container::set(EmploymentHistoryRepositoryInterface::class, fn() => new WpdbEmploymentHistoryRepository($GLOBALS['wpdb']));

        // Container wiring
        Container::set('quotation-repo',  fn() => Container::get(QuoteRepositoryInterface::class));
        // Container::set('employment-history-repo',  fn() => Container::get(EmploymentHistoryRepositoryInterface::class));

        // Container::set('quote-service',  fn() => new QuoteService(Container::get('quotation-repo')));
        // Container::set('employment-history-service',  fn() => new EmploymentHistoryService(Container::get('employment-history-repo')));

        // add_action('admin_init', function () {
        //     CustomerController::register();
        // });
    }
}
