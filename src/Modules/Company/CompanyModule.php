<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Company;

use TMT\CRM\Shared\Container\Container;

use TMT\CRM\Modules\Company\Domain\Repositories\CompanyRepositoryInterface;
use TMT\CRM\Modules\Customer\Domain\Repositories\UserRepositoryInterface;
use TMT\CRM\Modules\Company\Presentation\Admin\Controller\CompanyController;
use TMT\CRM\Modules\Company\Application\Services\{CompanyService};
use TMT\CRM\Modules\Company\Infrastructure\Persistence\{WpdbCompanyRepository};
use TMT\CRM\Modules\Customer\Infrastructure\Persistence\WpdbUserRepository;

final class CompanyModule
{
    public static function register(): void
    {
        //---------------------
        // Bind theo Interface
        //---------------------
        Container::set(CompanyRepositoryInterface::class,       fn() => new WpdbCompanyRepository($GLOBALS['wpdb']));
        Container::set(UserRepositoryInterface::class,  fn() => new WpdbUserRepository($GLOBALS['wpdb']));

        // Container wiring
        Container::set('company-repo',   fn() => Container::get(CompanyRepositoryInterface::class));
        Container::set('user-repo',   fn() => Container::get(UserRepositoryInterface::class));

        Container::set('company-service',   fn() => new CompanyService(Container::get('company-repo'), Container::get('company-contact-repo'), Container::get('user-repo')));

        \TMT\CRM\Modules\Company\Presentation\Admin\Ajax\CompanyAjaxController::bootstrap();

        add_action('admin_init', function () {
            CompanyController::register();
        });
    }
}
