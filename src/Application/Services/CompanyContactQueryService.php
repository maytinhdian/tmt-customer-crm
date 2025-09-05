<?php

namespace TMT\CRM\Application\Services;

use TMT\CRM\Application\DTO\CompanyContactViewDTO;
use TMT\CRM\Domain\Repositories\CompanyContactRepositoryInterface;
use TMT\CRM\Domain\Repositories\CustomerRepositoryInterface;
use TMT\CRM\Domain\Repositories\UserRepositoryInterface;

final class CompanyContactQueryService
{
    public function __construct(
        private CompanyContactRepositoryInterface $contact_repo,
        private CustomerRepositoryInterface       $customer_repo,
        private UserRepositoryInterface           $user_repo
    ) {}

    /**
     * @return CompanyContactViewDTO[]
     */
    public function find_paged_view_by_company(
        int $company_id,
        int $page,
        int $per_page,
        array $filters = [],
        array $sort = []
    ): array {
        // 1) Lấy DTO thô của company_contacts
        $contacts = $this->contact_repo->find_paged_by_company($company_id, $page, $per_page, $filters, $sort);

        // 2) Gom id để tránh N+1
        $customer_ids = [];
        $owner_ids    = [];
        foreach ($contacts as $c) {
            if ($c->customer_id) {
                $customer_ids[] = (int)$c->customer_id;
            }
            if ($c->created_by) {
                $owner_ids[] = (int)$c->created_by;
            }
        }
        $customer_ids = array_values(array_unique($customer_ids));
        $owner_ids    = array_values(array_unique($owner_ids));

        // 3) Lấy map
        $customers = !empty($customer_ids) ? $this->customer_repo->find_by_ids($customer_ids) : [];
        $owners    = !empty($owner_ids) ? $this->user_repo->find_by_ids($owner_ids) : [];

        // 4) Map sang ViewDTO (ưu tiên fallback tên)
        $views = [];
        foreach ($contacts as $d) {
            $cust   = $d->customer_id ? ($customers[$d->customer_id] ?? null) : null;
            $owner  = $d->created_by ? ($owners[$d->created_by] ?? null) : null;

            $full_name = $cust?->name ?: ($d->contact_name ?? '');
            if ($full_name === '' && $d->customer_id) {
                $full_name = '#' . (int)$d->customer_id;
            }
            if ($full_name === '') {
                $full_name = '—';
            }

            $views[] = new CompanyContactViewDTO(
                id: (int)$d->id,
                company_id: (int)$d->company_id,
                customer_id: $d->customer_id ? (int)$d->customer_id : null,

                full_name: $full_name,
                phone: $cust?->phone,
                email: $cust?->email,

                role: $d->role,
                position: $d->role,
                start_date: $d->start_date,
                end_date: $d->end_date,
                is_primary: (bool)$d->is_primary,

                owner_id: $d->created_by ? (int)$d->created_by : null,
                owner_name: $owner?->display_name,
                owner_phone: $owner?->phone,    // nếu UserDTO có
                owner_email: $owner?->email
            );
        }

        return $views;
    }

    public function count_view_by_company(int $company_id, array $filters = []): int
    {
        return $this->contact_repo->count_by_company($company_id, $filters);
    }
}
