<?php
declare(strict_types=1);

namespace TMT\CRM\Core\ExportImport\Application\Services;

final class ValidationService
{
    /**
     * MVP: kiểm tra tối thiểu các field bắt buộc theo entity.
     */
    public function validate_row(string $entity_type, array $data): bool
    {
        $required = match ($entity_type) {
            'company'  => ['name'],
            'customer' => ['first_name', 'last_name'],
            'contact'  => ['full_name', 'company_id'],
            default    => [],
        };
        foreach ($required as $key) {
            if (!array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
                return false;
            }
        }
        return true;
    }
}
