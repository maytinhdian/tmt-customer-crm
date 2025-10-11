<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\Customer\Application\Validation;

use TMT\CRM\Modules\Customer\Domain\Repositories\CustomerRepositoryInterface;
use TMT\CRM\Modules\Customer\Application\DTO\CustomerDTO;

/**
 * Validate dữ liệu Customer trên DTO.
 * Trả về mảng lỗi dạng ['field' => 'message']. Rỗng = hợp lệ.
 */
final class CustomerValidator
{
    public function __construct(
        private CustomerRepositoryInterface $customers
    ) {}

    /** Validate cho tạo mới (id rỗng/0) */
    public function validateCreate(CustomerDTO $dto): array
    {
        return $this->validate($dto, isUpdate: false, currentId: null);
    }

    /** Validate cho cập nhật (id > 0) */
    public function validateUpdate(CustomerDTO $dto): array
    {
        $id = $dto->id ?? 0;
        return $this->validate($dto, isUpdate: true, currentId: $id > 0 ? $id : null);
    }

    /**
     * Luật chung dựa trên DTO.
     * @return array<string,string>
     */
    private function validate(CustomerDTO $dto, bool $isUpdate, ?int $currentId): array
    {
        $errors = [];

        // name: bắt buộc, 2–255 ký tự
        $name = trim((string)($dto->name ?? ''));
        if ($name === '') {
            $errors['name'] = __('Tên khách hàng là bắt buộc.', 'tmt-crm');
        } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            $errors['name'] = __('Tên phải từ 2 đến 255 ký tự.', 'tmt-crm');
        }

        // email: tùy chọn; nếu có thì đúng định dạng + (tuỳ chọn) unique
        $email = $dto->email ? (string)$dto->email : '';
        if ($email !== '') {
            if (!is_email($email)) {
                $errors['email'] = __('Email không hợp lệ.', 'tmt-crm');
            } elseif (method_exists($this->customers, 'exists_by_email')) {
                /** @phpstan-ignore-next-line */
                if ($this->customers->find_by_email_or_phone($email, $currentId)) {
                    $errors['email'] = __('Email đã tồn tại trong hệ thống.', 'tmt-crm');
                }
            }
        }

        // phone: tùy chọn; nếu có thì cho phép + số, 8–20 ký tự
        $re = '/^(?:\+?84|0)(?:3[2-9]|5[25689]|7[06-9]|8[1-9]|9[0-46-9])\d{7}$/';
        $phone = $dto->phone ? preg_replace('/\s+/', '', (string)$dto->phone) : '';
        if ($phone && !preg_match($re, $phone))  {
            $errors['phone'] = __('Số điện thoại không hợp lệ.', 'tmt-crm');
        }

        // owner_id: tùy chọn; nếu set thì phải tồn tại user
        if ($dto->owner_id !== null) {
            $owner_id = (int)$dto->owner_id;
            if ($owner_id > 0 && !get_user_by('ID', $owner_id)) {
                $errors['owner_id'] = __('Người phụ trách không tồn tại.', 'tmt-crm');
            }
        }

        return $errors;
    }
}
