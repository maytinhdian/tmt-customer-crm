<?php

declare(strict_types=1);

namespace TMT\CRM\Application\Validation;

use TMT\CRM\Domain\Repositories\CompanyContactRepositoryInterface;

final class CompanyContactValidator
{
    public function __construct(
        private CompanyContactRepositoryInterface $repo
    ) {}

    public function ensure_contact_belongs_company(int $contact_id, int $company_id): void
    {
        if (!$this->repo->contact_belongs_to_company($contact_id, $company_id)) {
            throw new \InvalidArgumentException(__('Liên hệ không thuộc công ty.', 'tmt-crm'));
        }
    }

    public function ensure_contact_active(int $contact_id): void
    {
        if (!$this->repo->is_contact_active($contact_id)) {
            throw new \RuntimeException(__('Liên hệ đã hết hiệu lực (end_date < hôm nay).', 'tmt-crm'));
        }
    }
}
