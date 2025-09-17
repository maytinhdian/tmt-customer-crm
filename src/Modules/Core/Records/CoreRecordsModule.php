<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\Core\Records;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Modules\Core\Records\Application\Services\HistoryService;
use TMT\CRM\Modules\Core\Records\Application\Services\TrashService;
use TMT\CRM\Modules\Core\Records\Application\Services\RetentionService;
use TMT\CRM\Modules\Core\Records\Infrastructure\Persistence\WpdbAuditLogRepository;
use TMT\CRM\Modules\Core\Records\Infrastructure\Persistence\WpdbArchiveRepository;
use TMT\CRM\Modules\Core\Records\Infrastructure\Migration\CoreAuditTablesMigrator;
use TMT\CRM\Modules\Core\Records\Presentation\Cron\RegisterCron;
use TMT\CRM\Modules\Core\Records\Presentation\Admin\Settings\RecordsSettingsScreen;

/**
 * CoreRecordsModule (bootstrap - file chính)
 * Đăng ký container services, migration, cron, settings.
 */
final class CoreRecordsModule
{
    /** Gọi 1 lần ở bootstrap (file chính) */
    public static function register(): void
    {
        // 1) Container wiring
        Container::set('core.records.audit_repo', fn() => new WpdbAuditLogRepository());
        Container::set('core.records.archive_repo', fn() => new WpdbArchiveRepository());

        Container::set('core.records.history_service', function () {
            return new HistoryService(
                Container::get('core.records.audit_repo'),
                Container::get('core.records.archive_repo')
            );
        });

        Container::set('core.records.trash_service', function () {
            return new TrashService(
                Container::get('core.records.history_service')
            );
        });

        Container::set('core.records.retention_service', function () {
            return new RetentionService();
        });

        // 2) Migration đảm bảo bảng tồn tại
        add_action('plugins_loaded', function () {
            CoreAuditTablesMigrator::ensure_tables();
        }, 5);

        // 3) Settings screen
        add_action('admin_menu', function () {
            RecordsSettingsScreen::register();
        }, 20);

        // 4) Cron đăng ký job dọn dẹp
        add_action('init', function () {
            RegisterCron::register();
        }, 10);
    }
}
