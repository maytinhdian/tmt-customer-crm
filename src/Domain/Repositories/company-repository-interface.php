<?php
// src/Domain/Repositories/company-repository-interface.php
namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Domain\Entities\Company;

interface Company_Repository_Interface {
    public function create(Company $c): int;
    public function update(Company $c): bool;
    public function find_by_id(int $id): ?Company;
}