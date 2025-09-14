<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Customer;

use TMT\CRM\Shared\Hooks;
use TMT\CRM\Shared\Container;
use TMT\CRM\Modules\Customer\Presentation\Admin\Screen\CustomerScreen;
use TMT\CRM\Modules\Customer\Presentation\Admin\Controller\CustomerController;
use TMT\CRM\Modules\Customer\Application\Services\{CustomerService, CustomerQueryService};
use TMT\CRM\Modules\Customer\Infrastructure\Persistence\{
    WpdbCustomerRepository,
    WpdbCustomerQueryRepository
};

final class CustomerModule
{
    public static function register(): void
    {
        // Container wiring
        Container::set('customer-repository', fn() => new WpdbCustomerRepository($GLOBALS['wpdb']));

       

        // Menu screen
        Hooks::action('admin_menu', [CustomerScreen::class, 'register_menu'], 20);

        // admin-post actions
        Hooks::action('admin_post_' . CustomerController::ACTION_SAVE,    [CustomerController::class, 'handle_save']);
        Hooks::action('admin_post_' . CustomerController::ACTION_TRASH,   [CustomerController::class, 'handle_trash']);
        Hooks::action('admin_post_' . CustomerController::ACTION_RESTORE, [CustomerController::class, 'handle_restore']);
    }
}
