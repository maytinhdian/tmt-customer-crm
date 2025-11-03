<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Validation;

abstract class BaseValidator implements ValidatorInterface
{
    /** Chuẩn hoá input trước khi check rules (override nếu cần) */
    protected function normalize(array $input): array
    {
        return $input;
    }

    /** Điểm vào chung */
    final public function validate(array $input): ValidationResult
    {
        $data = $this->normalize($input);
        $res  = new ValidationResult($data, []);

        $this->rules($data, $res);

        return $res;
    }

    /** Child class khai báo rule tại đây */
    abstract protected function rules(array $data, ValidationResult $res): void;

    // --- Helpers tái sử dụng ---
    protected function required(mixed $v): bool
    {
        return !($v === null || $v === '' || (is_numeric($v) && $v === 0) || (is_array($v) && count($v) === 0));
    }

    /** XOR: đúng CHỈ MỘT trong hai điều kiện */
    protected function requireXor(bool $a, bool $b): bool
    {
        return $a xor $b;
    }

    /** Kiểm tra format YYYY-MM-DD HH:MM:SS (cho nhanh gọn) */
    protected function isDateTime(string $s): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}(?:\s+\d{2}:\d{2}:\d{2})?$/', $s)) return false;
        try {
            new \DateTimeImmutable($s);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
