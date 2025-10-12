<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Company;

use TMT\CRM\Shared\Container\Container;

use TMT\CRM\Modules\Company\Domain\Repositories\CompanyRepositoryInterface;
use TMT\CRM\Modules\Company\Presentation\Admin\Controller\CompanyController;
use TMT\CRM\Modules\Company\Application\Validation\CompanyValidator;
use TMT\CRM\Modules\Company\Application\Services\{CompanyService};
use TMT\CRM\Modules\Company\Infrastructure\Persistence\{WpdbCompanyRepository};

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
        // Container::set('repo.softdelete.company', fn() => new WpdbCompanyRepository($GLOBALS['wpdb']));

        // Validator
        Container::set(CompanyValidator::class, function () {
            /** @var CompanyRepositoryInterface $repo */
            $repo = Container::get(CompanyRepositoryInterface::class);
            return new CompanyValidator($repo);
        });
        Container::set('company-service',   fn() => new CompanyService(Container::get('company-repo'), Container::get('company-contact-repo'), Container::get(CompanyValidator::class), Container::get('user-repo')));

        \TMT\CRM\Modules\Company\Presentation\Admin\Ajax\CompanyAjaxController::bootstrap();

        // 2) Controller: admin-post actions (đăng ký luôn tại đây)
        CompanyController::register();
    }
}
