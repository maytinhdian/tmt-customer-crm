<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Workflow\Application\Services;

final class WorkflowValidator
{
    public function validate(array $workflow): bool
    {
        // Kiểm tra tối thiểu
        return isset($workflow['trigger_key'], $workflow['actions']) && is_array($workflow['actions']);
    }
}
