<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Workflow\Application\Services;

final class WorkflowRunner
{
    /**
     * Chạy danh sách actions theo thứ tự.
     * Tối giản: chỉ minh hoạ, tuỳ dự án nối vào Notifications/Files/... ở đây.
     */
    public function run_actions(array $actions, array $context = []): void
    {
        foreach ($actions as $action) {
            $type = (string)($action['type'] ?? 'log');
            $payload = (array)($action['payload'] ?? []);

            switch ($type) {
                case 'notify':
                    do_action('tmt_crm_notify', $payload, $context);
                    break;
                case 'webhook':
                    do_action('tmt_crm_webhook', $payload, $context);
                    break;
                case 'update_record':
                    do_action('tmt_crm_update_record', $payload, $context);
                    break;
                default:
                    do_action('tmt_crm_workflow_log', [
                        'message' => 'Action executed',
                        'type' => $type,
                        'payload' => $payload,
                        'context' => $context
                    ]);
            }
        }
    }
}
