<?php

namespace TMT\CRM\Modules\Customer\Application\Services;

use TMT\CRM\Core\Events\Domain\Events\DefaultEvent;
use TMT\CRM\Core\Events\Domain\ValueObjects\EventMetadata;
use TMT\CRM\Core\Events\Domain\Contracts\EventBusInterface;
use TMT\CRM\Shared\Container\Container;

use TMT\CRM\Shared\Logging\LoggerInterface;
use TMT\CRM\Modules\Customer\Application\DTO\CustomerDTO;
use TMT\CRM\Modules\Customer\Application\DTO\EmploymentHistoryDTO;

use TMT\CRM\Modules\Customer\Domain\Repositories\CustomerRepositoryInterface;
use \TMT\CRM\Modules\Customer\Domain\Repositories\EmploymentHistoryRepositoryInterface;

use TMT\CRM\Modules\Customer\Application\Validation\CustomerValidator;

class CustomerService
{

    public function __construct(
        private CustomerRepositoryInterface $customer_repo,
        private EmploymentHistoryRepositoryInterface $history_repo,
        private LoggerInterface $logger,
        private CustomerValidator $validator
    ) {}

    public function create(CustomerDTO $dto): array
    {
        $errors = $this->validator->validateCreate($dto);
        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }

        // Tạo 1 request_id để trace cùng 1 luồng
        $request_id = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : bin2hex(random_bytes(8));

        // Ghi log bắt đầu
        $this->logger->info('Customer create: start', [
            'request_id'  => $request_id,
            'name'        => $dto->name ?? '',
            'phone'       => $dto->phone ?? null,
            'email'       => $dto->email ?? null,
            'created_by'  => get_current_user_id(),
            'ip'          => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? null),
            'module'      => 'customer',
            'action'      => 'create',
        ]);

        try {
            // Thực hiện insert
            $customer_id = $this->customer_repo->create($dto);

            // Log thành công
            $this->logger->info('Customer create: success', [
                'request_id'   => $request_id,
                'customer_id'  => $customer_id,
                'name'         => $dto->name ?? '',
                'created_by'   =>  get_current_user(),
                'module'       => 'customer',
                'action'       => 'create',
            ]);

            $event = new DefaultEvent(
                'CustomerCreated',
                (object)['customer' => $dto],
                new EventMetadata(
                    event_id: wp_generate_uuid4(),
                    occurred_at: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
                    actor_id: get_current_user_id(),
                    correlation_id: $_REQUEST['tmt_correlation_id'] ?? null,
                )
            );
            /** @var EventBusInterface $bus */
            $bus = Container::get(EventBusInterface::class);
            $bus->publish($event);

            return ['ok' => (bool)$customer_id, 'errors' => [], 'id' => (int)$customer_id];
        } catch (\Throwable $e) {
            // Log lỗi
            $this->logger->error('Customer create: failed', [
                'request_id' => $request_id,
                'error'      => $e->getMessage(),
                'module'     => 'customer',
                'action'     => 'create',
            ]);
            throw $e; // giữ nguyên flow ném ra để UI/Controller xử lý
        }
    }



    public function update(int $id, CustomerDTO $dto): array
    {
        $errors = $this->validator->validateUpdate($dto);
        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }

        $ok = $this->customer_repo->update($id, $dto);
        return ['ok' => (bool)$ok, 'errors' => []];
    }

    public function list_paginated(int $page, int $per_page, array $filters = []): array
    {
        return $this->customer_repo->list_paginated($page, $per_page, $filters);
    }

    public function count_all(array $filters = []): int
    {
        return $this->customer_repo->count_all($filters);
    }
    public function get_by_id(int $id): ?CustomerDTO
    {
        return $this->customer_repo->find_by_id($id);
    }

    public function get_current_company(int $customer_id): ?EmploymentHistoryDTO
    {
        return $this->history_repo->find_current_company_of_customer($customer_id);
    }

    public function soft_delete(int $id, ?int $actor_id = null, string $reason = ''): bool
    {
        $actor_id = $actor_id ?? get_current_user_id();
        return $this->customer_repo->soft_delete($id, (int)$actor_id, $reason);
    }

    public function restore(int $id): bool
    {
        return $this->customer_repo->restore($id);
    }

    public function purge(int $id): bool
    {
        return $this->customer_repo->purge($id);
    }
}
