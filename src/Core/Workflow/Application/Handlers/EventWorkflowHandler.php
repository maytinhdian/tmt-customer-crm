<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Workflow\Application\Handlers;

use TMT\CRM\Core\Workflow\Application\Services\WorkflowEngine;

/**
 * Subscriber/Handler để nối với Core/Events
 */
final class EventWorkflowHandler
{
    public function __construct(private WorkflowEngine $engine) {}

    /** Chuẩn hoá payload từ EventBus về (event_key, context) */
    public function __invoke(array|object $payload): void
    {
        $event_key = '';
        $context = [];

        if (is_array($payload)) {
            $event_key = (string)($payload['event_key'] ?? 'unknown_event');
            $context   = (array)($payload['context'] ?? []);
        } elseif (is_object($payload) && method_exists($payload, 'name') && method_exists($payload, 'payload')) {
            $event_key = (string)$payload->name();
            $context   = (array)($payload->payload());
        }

        if ($event_key) {
            $this->engine->handle_event($event_key, $context);
        }
    }
}
