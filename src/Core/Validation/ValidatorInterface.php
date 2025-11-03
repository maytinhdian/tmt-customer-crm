<?php

namespace TMT\CRM\Core\Validation;

interface ValidatorInterface
{
    public function validate(array $input): ValidationResult;
}
