<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Files;

use TMT\CRM\Core\Files\Infrastructure\Migrations\FileMigrator;
use TMT\CRM\Core\Files\Presentation\Controllers\DownloadController;
use TMT\CRM\Core\Files\Presentation\Controllers\UploadController;
use TMT\CRM\Core\Files\Presentation\Controllers\ViewController;
use TMT\CRM\Core\Files\Presentation\Admin\Settings\FilesSettingsIntegration;
use TMT\CRM\Core\Files\Infrastructure\Providers\FilesServiceProvider;
use TMT\CRM\Core\Files\Presentation\Controllers\FilesPlaygroundController;

final class FilesModule
{
    public static function bootstrap(): void
    {
        // Register migrator to global Installer registry
        add_filter('tmt_crm_migrators', static function (array $migrators) {
            $migrators[] = FileMigrator::class;
            return $migrators;
        });
        FilesServiceProvider::register();
        // Routes
        DownloadController::bootstrap();
        ViewController::bootstrap();
        UploadController::bootstrap();
        FilesPlaygroundController::bootstrap();
        // Settings (optional, enable when Core/Settings ready)
        if (class_exists(FilesSettingsIntegration::class)) {
            FilesSettingsIntegration::register();
        }
    }
}
