<?php

namespace TMT\CRM\Modules\Company\Application\Exceptions;

final class ValidationException extends \RuntimeException
{
    /** @var array<string,string> */
    private array $fields;
    /** @param array<string,string> $fields */
    public function __construct(array $fields, string $message = '')
    {
        parent::__construct($message ?: __('Dữ liệu không hợp lệ.', 'tmt-crm'));
        $this->fields = $fields;
    }
    /** @return array<string,string> */
    public function fields(): array
    {
        return $this->fields;
    }
}
