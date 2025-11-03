<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Validation;

final class ValidationResult
{
    public function __construct(
        private array $data,
        private array $errors = []
    ) {}

    public function data(): array { return $this->data; }

    public function errors(): array { return $this->errors; }

    public function failed(): bool { return !empty($this->errors); }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
