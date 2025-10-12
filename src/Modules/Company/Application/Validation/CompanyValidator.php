<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\Company\Application\Validation;

use TMT\CRM\Modules\Company\Application\DTO\CompanyDTO;
use TMT\CRM\Modules\Company\Domain\Repositories\CompanyRepositoryInterface;

final class CompanyValidator
{
    public const MAX_NAME        = 191;
    public const MAX_ADDRESS     = 255;
    public const MAX_EMAIL       = 191;
    public const MAX_WEBSITE     = 255;
    public const MAX_PHONE       = 32;
    public const MAX_REPRESENTER = 191;
    public const MAX_NOTE        = 1000;

    /** VN tax code: 10 digits or 10-3 sub-branch */
    private const REGEX_TAX_CODE = '/^[0-9]{10}(-[0-9]{3})?$/';
    /** Phone: digits, space, + - ( ) */
    private const REGEX_PHONE    = '/^[0-9+\-\s()]{6,}$/';

    public function __construct(
        private CompanyRepositoryInterface $companies
    ) {}

    /**
     * Validate cho tạo mới. Trả về mảng lỗi dạng ['field' => 'message'].
     * Không có lỗi => mảng rỗng.
     *
     * @param array<string,mixed> $data
     * @return array<string,string>
     */
    public function validateForCreate(array $data): array
    {
        $clean = $this->normalize($data);
        $errors = [];

        // Required
        $this->requireNotEmpty($errors, $clean, 'name',     __('Tên công ty là bắt buộc.', 'tmt-crm'));
        $this->requireNotEmpty($errors, $clean, 'tax_code', __('Mã số thuế là bắt buộc.', 'tmt-crm'));
        $this->requireNotEmpty($errors, $clean, 'address',  __('Địa chỉ là bắt buộc.', 'tmt-crm'));

        // Length
        $this->maxLength($errors, $clean, 'name',        self::MAX_NAME);
        $this->maxLength($errors, $clean, 'address',     self::MAX_ADDRESS);
        $this->maxLength($errors, $clean, 'email',       self::MAX_EMAIL);
        $this->maxLength($errors, $clean, 'website',     self::MAX_WEBSITE);
        $this->maxLength($errors, $clean, 'phone',       self::MAX_PHONE);
        $this->maxLength($errors, $clean, 'representer', self::MAX_REPRESENTER);
        $this->maxLength($errors, $clean, 'note',        self::MAX_NOTE);

        // Formats
        if (!isset($errors['tax_code']) && $clean['tax_code'] !== '' && !preg_match(self::REGEX_TAX_CODE, $clean['tax_code'])) {
            $errors['tax_code'] = __('Mã số thuế không đúng định dạng (10 hoặc 10-3 chữ số).', 'tmt-crm');
        }

        if ($clean['email'] !== '' && function_exists('is_email') && !is_email($clean['email'])) {
            $errors['email'] = __('Email không hợp lệ.', 'tmt-crm');
        }

        if ($clean['website'] !== '' && !filter_var($clean['website'], FILTER_VALIDATE_URL)) {
            $errors['website'] = __('Website không phải URL hợp lệ.', 'tmt-crm');
        }

        if ($clean['phone'] !== '' && !preg_match(self::REGEX_PHONE, $clean['phone'])) {
            $errors['phone'] = __('Số điện thoại không hợp lệ.', 'tmt-crm');
        }

        // Owner (optional nhưng nếu có phải tồn tại)
        if ($clean['owner_id'] !== null && $clean['owner_id'] > 0) {
            if (function_exists('get_user_by') && !get_user_by('id', (int)$clean['owner_id'])) {
                $errors['owner_id'] = __('Người phụ trách không tồn tại.', 'tmt-crm');
            }
        }

        // Uniqueness: tax_code
        if (!isset($errors['tax_code']) && $clean['tax_code'] !== '') {
            $existing = $this->companies->find_by_tax_code($clean['tax_code']);
            if ($existing) {
                $errors['tax_code'] = __('Mã số thuế đã tồn tại.', 'tmt-crm');
            }
        }

        return $errors;
    }

    /**
     * Validate cho cập nhật (bỏ qua trùng chính bản ghi theo $id).
     *
     * @param int $id ID công ty đang cập nhật
     * @param array<string,mixed> $data
     * @return array<string,string>
     */
    public function validateForUpdate(int $id, array $data): array
    {
        $clean = $this->normalize($data);
        $errors = [];

        // Required
        $this->requireNotEmpty($errors, $clean, 'name',     __('Tên công ty là bắt buộc.', 'tmt-crm'));
        $this->requireNotEmpty($errors, $clean, 'tax_code', __('Mã số thuế là bắt buộc.', 'tmt-crm'));
        $this->requireNotEmpty($errors, $clean, 'address',  __('Địa chỉ là bắt buộc.', 'tmt-crm'));

        // Length
        $this->maxLength($errors, $clean, 'name',        self::MAX_NAME);
        $this->maxLength($errors, $clean, 'address',     self::MAX_ADDRESS);
        $this->maxLength($errors, $clean, 'email',       self::MAX_EMAIL);
        $this->maxLength($errors, $clean, 'website',     self::MAX_WEBSITE);
        $this->maxLength($errors, $clean, 'phone',       self::MAX_PHONE);
        $this->maxLength($errors, $clean, 'representer', self::MAX_REPRESENTER);
        $this->maxLength($errors, $clean, 'note',        self::MAX_NOTE);

        // Formats
        if (!isset($errors['tax_code']) && $clean['tax_code'] !== '' && !preg_match(self::REGEX_TAX_CODE, $clean['tax_code'])) {
            $errors['tax_code'] = __('Mã số thuế không đúng định dạng (10 hoặc 10-3 chữ số).', 'tmt-crm');
        }

        if ($clean['email'] !== '' && function_exists('is_email') && !is_email($clean['email'])) {
            $errors['email'] = __('Email không hợp lệ.', 'tmt-crm');
        }

        if ($clean['website'] !== '' && !filter_var($clean['website'], FILTER_VALIDATE_URL)) {
            $errors['website'] = __('Website không phải URL hợp lệ.', 'tmt-crm');
        }

        if ($clean['phone'] !== '' && !preg_match(self::REGEX_PHONE, $clean['phone'])) {
            $errors['phone'] = __('Số điện thoại không hợp lệ.', 'tmt-crm');
        }

        // Owner (optional)
        if ($clean['owner_id'] !== null && $clean['owner_id'] > 0) {
            if (function_exists('get_user_by') && !get_user_by('id', (int)$clean['owner_id'])) {
                $errors['owner_id'] = __('Người phụ trách không tồn tại.', 'tmt-crm');
            }
        }

        // Uniqueness: tax_code (exclude current)
        if (!isset($errors['tax_code']) && $clean['tax_code'] !== '') {
            $existing = $this->companies->find_by_tax_code($clean['tax_code']);
            if ($existing && (int)$existing->id !== (int)$id) {
                $errors['tax_code'] = __('Mã số thuế đã tồn tại ở bản ghi khác.', 'tmt-crm');
            }
        }

        return $errors;
    }

    /**
     * Helper: nhận DTO.
     *
     * @return array<string,string>
     */
    public function validateDtoForCreate(CompanyDTO $dto): array
    {
        return $this->validateForCreate($dto->to_array());
    }

    /**
     * @return array<string,string>
     */
    public function validateDtoForUpdate(int $id, CompanyDTO $dto): array
    {
        return $this->validateForUpdate($id, $dto->to_array());
    }

    /**
     * Chuẩn hoá input: trim & ép kiểu nhẹ.
     *
     * @param array<string,mixed> $data
     * @return array{
     *   owner_id: ?int,
     *   name: string,
     *   tax_code: string,
     *   address: string,
     *   phone: string,
     *   email: string,
     *   website: string,
     *   representer: string,
     *   note: string
     * }
     */
    private function normalize(array $data): array
    {
        $t = static fn($v) => is_string($v) ? trim($v) : (is_null($v) ? '' : (string)$v);

        return [
            'owner_id'    => isset($data['owner_id']) ? (int)$data['owner_id'] ?: null : null,
            'name'        => $t($data['name']        ?? ''),
            'tax_code'    => strtoupper($t($data['tax_code'] ?? '')),
            'address'     => $t($data['address']     ?? ''),
            'phone'       => $t($data['phone']       ?? ''),
            'email'       => strtolower($t($data['email'] ?? '')),
            'website'     => $t($data['website']     ?? ''),
            'representer' => $t($data['representer'] ?? ''),
            'note'        => $t($data['note']        ?? ''),
        ];
    }

    /**
     * @param array<string,string>          $errors
     * @param array<string,string|int|null> $data
     */
    private function requireNotEmpty(array &$errors, array $data, string $field, string $message): void
    {
        if (isset($errors[$field])) {
            return;
        }
        $val = $data[$field] ?? '';
        $val = is_string($val) ? trim($val) : $val;
        if ($val === '' || $val === null) {
            $errors[$field] = $message;
        }
    }

    /**
     * @param array<string,string>          $errors
     * @param array<string,string|int|null> $data
     */
    private function maxLength(array &$errors, array $data, string $field, int $max): void
    {
        if (isset($errors[$field])) {
            return;
        }
        $val = (string)($data[$field] ?? '');
        if ($val !== '' && mb_strlen($val) > $max) {
            /* translators: %1$s: field, %2$d: max length */
            $errors[$field] = sprintf(__('Trường %1$s vượt quá %2$d ký tự.', 'tmt-crm'), $field, $max);
        }
    }
}
