<?php
// src/Application/Services/company-service.php
namespace TMT\CRM\Application\Services;


use TMT\CRM\Application\DTO\CompanyDTO;
use TMT\CRM\Domain\Entities\Company;
use TMT\CRM\Domain\Repositories\CompanyRepositoryInterface as Repo;


final class CompanyService
{
    public function __construct(private Repo $repo) {}


    public function create(CompanyDTO $d): int
    {
        $e = new Company(
            id: null,
            name: $d->name,
            tax_code: $d->tax_code,
            address: $d->address,
            contact_person: $d->contact_person,
            phone: $d->phone,
            email: $d->email
        );
        return $this->repo->create($e);
    }


    public function update(int $id, CompanyDTO $d): bool
    {
        $e = $this->repo->find_by_id($id);
        if (!$e) return false;
        $e->name = $d->name;
        $e->tax_code = $d->tax_code;
        $e->address = $d->address;
        $e->contact_person = $d->contact_person;
        $e->phone = $d->phone;
        $e->email = $d->email;
        return $this->repo->update($e);
    }


    public function find(int $id): ?Company
    {
        return $this->repo->find_by_id($id);
    }
}
