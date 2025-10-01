<?php
declare(strict_types=1);

namespace TMT\CRM\Core\ExportImport\Infrastructure\Setup;

use TMT\CRM\Core\ExportImport\ExportImportModule;
use TMT\CRM\Core\ExportImport\Infrastructure\Setup\Migrator;

final class Installer
{
    public static function maybe_install(): void
    {
        $installed = get_option(ExportImportModule::OPTION_VERSION);
        if ($installed === ExportImportModule::VERSION) { return; }

        (new Migrator())->install_or_upgrade($installed ?: '0.0.0', ExportImportModule::VERSION);
        update_option(ExportImportModule::OPTION_VERSION, ExportImportModule::VERSION);
    }
}
