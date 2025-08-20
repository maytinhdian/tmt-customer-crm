<?php
namespace TMT\CRM\Application\Services;

use TMT\CRM\Application\DTO\Customer_DTO;
use TMT\CRM\Domain\Entities\Customer;
use TMT\CRM\Domain\Repositories\Customer_Repository_Interface;

final class Customer_Service {
    public function __construct(private Customer_Repository_Interface $repo) {}

    public function create(Customer_DTO $dto): int {
        $customer = new Customer(null, $dto->full_name, $dto->phone, $dto->email, $dto->company_id, $dto->address, $dto->tags, $dto->note);
        return $this->repo->create($customer);
    }

    public function update(int $id, Customer_DTO $dto): bool {
        $customer = new Customer($id, $dto->full_name, $dto->phone, $dto->email, $dto->company_id, $dto->address, $dto->tags, $dto->note);
        return $this->repo->update($customer);
    }

    public function find(int $id): ?Customer {
        return $this->repo->find_by_id($id);
    }
}