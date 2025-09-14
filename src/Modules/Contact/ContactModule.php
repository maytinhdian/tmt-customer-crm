<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Contact;

use TMT\CRM\Shared\Container;
use TMT\CRM\Modules\Contact\Presentation\Admin\Controller\CompanyContactController;
use TMT\CRM\Modules\Contact\Domain\Repositories\CompanyContactRepositoryInterface;
use TMT\CRM\Modules\Contact\Infrastructure\Persistence\WpdbCompanyContactRepository;
use TMT\CRM\Modules\Contact\Application\Validation\CompanyContactValidator;
use TMT\CRM\Modules\Contact\Application\Services\{CompanyContactService,CompanyContactQueryService};


final class ContactModule
{
    public static function register(): void
    {
        //---------------------
        // Bind theo Interface
        //---------------------
        Container::set(CompanyContactRepositoryInterface::class,       fn() => new WpdbCompanyContactRepository($GLOBALS['wpdb']));

        // ------------------------
        // Validator (đăng ký để tái sử dụng ở nhiều service/controller)
        // -------------------------
        Container::set('company-contact-validator', fn() => new CompanyContactValidator(
            Container::get('company-contact-repo')
        ));


        // Container wiring
        Container::set('company-contact-repo', fn() => Container::get(CompanyContactRepositoryInterface::class));
        Container::set('company-contact-service',  fn() => new CompanyContactService(Container::get('company-contact-repo'), Container::get('customer-repo'), Container::get('company-repo'), Container::get('company-contact-validator')));
        Container::set('company-contact-query-service',  fn() => new CompanyContactQueryService(Container::get('company-contact-repo'), Container::get('customer-repo'), Container::get('user-repo'), Container::get('company-repo')));
        add_action('admin_init', function () {
            CompanyContactController::register();
        });
    }
}
