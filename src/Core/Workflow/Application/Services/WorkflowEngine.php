<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Workflow\Application\Services;

use TMT\CRM\Domain\Repositories\WorkflowRepositoryInterface;

final class WorkflowEngine
{
    public function __construct(
        private WorkflowRepositoryInterface $workflows,
        private WorkflowRunner $runner,
        private WorkflowValidator $validator
    ) {}

    /** Nhận event_key và context từ EventBus */
    public function handle_event(string $event_key, array $context = []): void
    {
        $list = $this->workflows->list_all(true);
        foreach ($list as $wf) {
            if ($wf->trigger_key !== $event_key) {
                continue;
            }
            $workflow_arr = $wf->to_array();
            if (!$this->validator->validate($workflow_arr)) {
                continue;
            }
            // TODO: evaluate conditions nếu cần
            $this->runner->run_actions($wf->actions, $context);
        }
    }
}
