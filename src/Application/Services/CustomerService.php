<?php
namespace TMT\CRM\Application\Services;

use TMT\CRM\Application\DTO\CustomerDTO;
use TMT\CRM\Domain\Entities\Customer;
use TMT\CRM\Domain\Repositories\CustomerRepositoryInterface;

final class CustomerService {
    public function __construct(private CustomerRepositoryInterface $repo) {}

    public function create(CustomerDTO $dto): int {
        $customer = new Customer(null, $dto->full_name, $dto->phone, $dto->email, $dto->company_id, $dto->address, $dto->tags, $dto->note);
        return $this->repo->create($customer);
    }

    public function update(int $id, CustomerDTO $dto): bool {
        $customer = new Customer($id, $dto->full_name, $dto->phone, $dto->email, $dto->company_id, $dto->address, $dto->tags, $dto->note);
        return $this->repo->update($customer);
    }

    public function find(int $id): ?Customer {
        return $this->repo->find_by_id($id);
    }
}