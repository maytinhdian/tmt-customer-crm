<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Workflow;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Workflow\Infrastructure\Providers\WorkflowServiceProvider;
use TMT\CRM\Core\Workflow\Infrastructure\Migration\WorkflowMigrator;

/**
 * WorkflowModule
 * - bootstrap (file chính): đăng ký ServiceProvider, Migrator, UI.
 */
final class WorkflowModule
{
    public const VERSION = '0.1.0';
    public const OPTION_VERSION = 'tmt_crm_workflow_version';

    public static function bootstrap(): void
    {
        // Đăng ký DI qua ServiceProvider
        WorkflowServiceProvider::register();

        // Đăng ký migrator vào Installer orchestrator (nếu dự án đã có filter này)
        add_filter('tmt_crm_schema_migrators', static function (array $migrators) {
            $migrators[] = WorkflowMigrator::class;
            return $migrators;
        });

        // (Tùy chọn) Đăng ký UI admin (list screen) tại đây nếu muốn tự tạo menu
        \TMT\CRM\Core\Workflow\Presentation\Admin\Screen\WorkflowListScreen::register_menu();
    }
}
