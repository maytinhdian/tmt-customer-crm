<?php

declare(strict_types=1);

namespace TMT\CRM\Core\ExportImport;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\ExportImport\Infrastructure\Setup\Installer;
use TMT\CRM\Core\ExportImport\Presentation\Admin\Screen\ExportImportScreen;
use TMT\CRM\Core\ExportImport\Presentation\Admin\Controller\ExportImportController;
use TMT\CRM\Core\ExportImport\Infrastructure\Providers\ExportImportServiceProvider;
/**
 * Module Export/Import — MVP
 * bootstrap (file chính)
 */
final class ExportImportModule
{
    public const VERSION = '0.1.0';
    public const OPTION_VERSION = 'tmt_crm_export_import_version';
    public const MODULE_KEY = 'export_import';

    public static function bootstrap(): void
    {
        // Cài đặt DB khi cần
        // add_action('init', [Installer::class, 'maybe_install']);
        ExportImportServiceProvider::register();
        // Đăng ký màn hình admin
        add_action('admin_menu', [ExportImportScreen::class, 'register_menu'], 20);

        // admin-post endpoints
        add_action('admin_post_tmt_crm_export_start', [ExportImportController::class, 'handle_export_start']);
        add_action('admin_post_tmt_crm_import_preview', [ExportImportController::class, 'handle_import_preview']);
        add_action('admin_post_tmt_crm_import_commit', [ExportImportController::class, 'handle_import_commit']);
    }
}
