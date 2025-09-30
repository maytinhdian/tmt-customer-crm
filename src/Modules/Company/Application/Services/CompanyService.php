<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Company\Application\Services;

use TMT\CRM\Modules\Company\Application\DTO\CompanyDTO;

use TMT\CRM\Modules\Company\Domain\Repositories\CompanyRepositoryInterface;
use TMT\CRM\Modules\Contact\Domain\Repositories\CompanyContactRepositoryInterface;
use TMT\CRM\Modules\Customer\Domain\Repositories\UserRepositoryInterface;

use TMT\CRM\Core\Events\Domain\Contracts\EventInterface;
use TMT\CRM\Core\Events\Domain\ValueObjects\EventMetadata;
use TMT\CRM\Core\Events\Domain\Contracts\EventBusInterface;
use TMT\CRM\Shared\Container\Container;

use TMT\CRM\Shared\EventBus\EventBus;

final class CompanyService
{
    public const ROLE_ACCOUNTING = 'accounting';
    public const ROLE_PURCHASING = 'purchasing';
    public const ROLE_INVOICE    = 'invoice';


    public function __construct(
        private CompanyRepositoryInterface $company_repo,
        private CompanyContactRepositoryInterface $contact_repo,
        private UserRepositoryInterface $user_repo
    ) {}


    /** Tạo mới công ty (validate + chống trùng MST) */
    public function create(array $data): int
    {
        $dto = $this->build_dto_from_array($data);
        $this->validate_required($dto);
        $this->ensure_unique_tax_code($dto->tax_code, null);
        $company_id = $this->company_repo->insert($dto);

        // (2) Tạo event ẩn danh implements EventInterface
        $event = new class($dto) implements EventInterface {
            public function __construct(private CompanyDTO $payload) {}
            public function name(): string
            {
                return 'CompanyCreated';
            }
            public function payload(): object
            {
                return $this->payload;
            }
            public function metadata(): EventMetadata
            {
                return new EventMetadata(
                    event_id: wp_generate_uuid4(),
                    occurred_at: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
                    actor_id: get_current_user_id(),
                    correlation_id: $_REQUEST['tmt_correlation_id'] ?? null,
                );
            }
        };

        // (3) Publish qua EventBusInterface
        /** @var EventBusInterface $bus */
        $bus = Container::get(EventBusInterface::class);
        $bus->publish($event);

        // 5) Trả về
        return $company_id;
    }

    /** Cập nhật công ty */
    public function update(int $id, array $data): bool
    {
        $dto = $this->build_dto_from_array($data, $id);
        $this->validate_required($dto);
        $this->ensure_unique_tax_code($dto->tax_code, $id);
        return $this->company_repo->update($dto);
    }

    public function delete(int $id): bool
    {
        return $this->company_repo->delete($id);
    }

    public function find_by_id(int $id): ?CompanyDTO
    {
        return $this->company_repo->find_by_id($id);
    }

    /**
     * Trả về [items, total] để tiện cho WP_List_Table
     * @return array{items: CompanyDTO[], total: int}
     */
    public function get_paged(int $page, int $per_page, array $filters = []): array
    {
        $page     = max(1, $page);
        $per_page = max(1, $per_page);

        $items = $this->company_repo->list_paginated($page, $per_page, $filters);
        $items = $this->enrich_deleted_meta($items); // 👈 gắn tên người xoá

        $total = $this->company_repo->count_all($filters);

        return ['items' => $items, 'total' => $total];
    }

    /** Xoá mềm (mark_deleted) */
    public function soft_delete(int $company_id, int $actor_id, ?string $reason = null): void
    {
        // $this->policy->ensure_can('company_soft_delete', $actor_id, $company_id);
        $this->company_repo->mark_deleted($company_id, $actor_id, $reason);

        // 4) PHÁT SỰ KIỆN – CompanyCreated
        //    Chèn ngay dưới phần create/commit thành công
        // Phát sự kiện cho Notifications
        EventBus::publish('CompanySoftDeleted', [
            'event_key' => 'CompanySoftDeleted',
            'context'   => [
                'actor_id'   => (int)$actor_id,
                'company_id' => (int)$company_id,
            ],
        ]);
    }

    /** Khôi phục */
    public function restore(int $company_id, int $actor_id): void
    {
        // $this->policy->ensure_can('company_restore', $actor_id, $company_id);
        $this->company_repo->restore($company_id, $actor_id);
    }

    /** Xoá vĩnh viễn */
    public function purge(int $company_id, int $actor_id): void
    {
        // $this->policy->ensure_can('company_purge', $actor_id, $company_id);
        $this->company_repo->purge($company_id, $actor_id);
    }

    /** Kiểm tra còn hoạt động */
    public function exists_active(int $company_id): bool
    {
        return $this->company_repo->exists_active($company_id);
    }


    /** Dùng cho Views: Tất cả / Đang hoạt động / Đã xoá */
    public function count_for_tabs(): array
    {
        return $this->company_repo->count_for_tabs();
    }


    // ================== helpers ==================

    /** @param CompanyDTO[] $items */
    private function enrich_deleted_meta(array $items): array
    {
        // Thu thập các user id cần tra
        $ids = [];
        foreach ($items as $dto) {
            if (!empty($dto->deleted_by)) {
                $ids[(int) $dto->deleted_by] = true;
            }
        }
        if (!$ids) {
            return $items;
        }

        $user_ids = array_keys($ids);

        // Giả sử UserRepo có hàm này: trả về [user_id => display_name]
        $name_map = $this->user_repo->map_display_names($user_ids);

        foreach ($items as $dto) {
            if (!empty($dto->deleted_by) && isset($name_map[(int) $dto->deleted_by])) {
                $dto->deleted_by_name = (string) $name_map[(int) $dto->deleted_by];
            }
        }
        return $items;
    }

    private function build_dto_from_array(array $data, ?int $id = null): CompanyDTO
    {
        return CompanyDTO::from_array($data);
    }

    private function validate_required(CompanyDTO $dto): void
    {
        $errors = [];
        if ($dto->name === '')     $errors[] = 'Tên công ty là bắt buộc.';
        if ($dto->tax_code === '') $errors[] = 'Mã số thuế là bắt buộc.';
        if ($dto->address === '')  $errors[] = 'Địa chỉ là bắt buộc.';


        // ✅ Kiểm tra MST Việt Nam
        if ($dto->tax_code !== '' && !$this->is_valid_vn_tax_code($dto->tax_code)) {
            $errors[] = 'Mã số thuế không hợp lệ (định dạng đúng: 10 số hoặc 10 số + "-XXX").';
        }

        if ($errors) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }
    }

    /**
     * Kiểm tra MST Việt Nam:
     * - 10 chữ số (tổ chức), hoặc
     * - 10 chữ số + "-" + 3 chữ số (đơn vị phụ thuộc), hoặc (tuỳ chọn)
     * - 13 chữ số liền (nếu muốn hỗ trợ nhập không có "-")
     */
    private function is_valid_vn_tax_code(string $tax_code): bool
    {
        $tax_code = trim($tax_code);

        // Nếu muốn CHỈ chấp nhận dạng có gạch: dùng pattern 1
        // $pattern = '/^\d{10}(-\d{3})?$/';

        // Nếu muốn cho phép cả 13 số liền: dùng pattern 2
        $pattern = '/^(?:\d{10}(?:-\d{3})?|\d{13})$/';

        return (bool) preg_match($pattern, $tax_code);
    }

    private function ensure_unique_tax_code(string $tax_code, ?int $exclude_id): void
    {
        $exists = $this->company_repo->find_by_tax_code($tax_code, $exclude_id);
        if ($exists) {
            throw new \RuntimeException('Mã số thuế đã tồn tại cho công ty khác.');
        }
    }

    private function nn(?string $v): ?string
    {
        $t = trim((string)$v);
        return $t !== '' ? $t : null;
    }
}
