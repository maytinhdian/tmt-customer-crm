<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Company;

use TMT\CRM\Shared\Container\Container;

use TMT\CRM\Modules\Company\Domain\Repositories\CompanyRepositoryInterface;
use TMT\CRM\Modules\Company\Presentation\Admin\Controller\CompanyController;
use TMT\CRM\Modules\Company\Application\Services\{CompanyService};
// use TMT\CRM\Modules\Contact\Infrastructure\Persistence\WpdbCompanyContactRepository;
use TMT\CRM\Modules\Company\Infrastructure\Persistence\{
    WpdbCompanyRepository,
};

final class CompanyModule
{
    public static function register(): void
    {
        //---------------------
        // Bind theo Interface
        //---------------------
        Container::set(CompanyRepositoryInterface::class,       fn() => new WpdbCompanyRepository($GLOBALS['wpdb']));

        // Container wiring
        Container::set('company-repo',   fn() => Container::get(CompanyRepositoryInterface::class));

        Container::set('company-service',   fn() => new CompanyService(Container::get('company-repo'), Container::get('company-contact-repo')));
        
        \TMT\CRM\Modules\Company\Presentation\Admin\Ajax\CompanyAjaxController::bootstrap();

        add_action('admin_init', function () {
            CompanyController::register();
        });
    }
}
