<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Workflow\Infrastructure\Providers;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Workflow\Application\Services\{WorkflowEngine, WorkflowRunner, WorkflowValidator};
use TMT\CRM\Core\Workflow\Application\Handlers\EventWorkflowHandler;
use TMT\CRM\Core\Workflow\Infrastructure\Persistence\DbWorkflowRepository;
use TMT\CRM\Domain\Repositories\WorkflowRepositoryInterface;

/** Đăng ký DI cho Workflow */
final class WorkflowServiceProvider
{
    public static function register(): void
    {
        Container::set(WorkflowRepositoryInterface::class, function () {
            return new DbWorkflowRepository();
        });

        Container::set(WorkflowValidator::class, fn() => new WorkflowValidator());
        Container::set(WorkflowRunner::class, fn() => new WorkflowRunner());

        Container::set(WorkflowEngine::class, function () {
            return new WorkflowEngine(
                Container::get(WorkflowRepositoryInterface::class),
                Container::get(WorkflowRunner::class),
                Container::get(WorkflowValidator::class),
            );
        });

        Container::set(EventWorkflowHandler::class, function () {
            return new EventWorkflowHandler(Container::get(WorkflowEngine::class));
        });

        // (Gợi ý) Gắn handler vào EventBus dự án của bạn
        add_action('tmt_crm_event', [Container::get(EventWorkflowHandler::class), '__invoke'], 10, 1);
    }
}
