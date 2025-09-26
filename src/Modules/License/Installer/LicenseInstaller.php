<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\License\Installer;

use TMT\CRM\Modules\License\Infrastructure\Migrations\LicenseMigrator;

final class LicenseInstaller
{
    public static function maybe_install(): void
    {
        // Gọi migrator của module để tạo bảng & cập nhật version.
        (new LicenseMigrator())->install();
    }
}
