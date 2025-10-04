<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Customer;

use TMT\CRM\Modules\Customer\Domain\Repositories\EmploymentHistoryRepositoryInterface;
use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Shared\Logging\LoggerInterface;
use TMT\CRM\Modules\Customer\Domain\Repositories\CustomerRepositoryInterface;
use TMT\CRM\Modules\Customer\Presentation\Admin\Controller\CustomerController;
use TMT\CRM\Modules\Customer\Application\Services\{CustomerService, EmploymentHistoryService};
use TMT\CRM\Modules\Customer\Infrastructure\Persistence\{
    WpdbCustomerRepository,
    WpdbEmploymentHistoryRepository
};

final class CustomerModule
{
    public static function register(): void
    {
        //---------------------
        // Bind theo Interface
        //---------------------
        Container::set(CustomerRepositoryInterface::class,       fn() => new WpdbCustomerRepository($GLOBALS['wpdb']));
        Container::set(EmploymentHistoryRepositoryInterface::class, fn() => new WpdbEmploymentHistoryRepository($GLOBALS['wpdb']));

        // Container wiring
        Container::set('customer-repo',  fn() => Container::get(CustomerRepositoryInterface::class));
        Container::set('employment-history-repo',  fn() => Container::get(EmploymentHistoryRepositoryInterface::class));

        Container::set('customer-service',  fn() => new CustomerService(Container::get('customer-repo'), Container::get(('employment-history-repo')),  Container::get(LoggerInterface::class)));
        Container::set('employment-history-service',  fn() => new EmploymentHistoryService(Container::get('employment-history-repo')));

        add_action('admin_init', function () {
            CustomerController::register();
        });
    }
}
