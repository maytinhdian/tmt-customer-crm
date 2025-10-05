<?php

declare(strict_types=1);

namespace TMT\CRM\Core\ExportImport\Infrastructure\Providers;

use TMT\CRM\Shared\Container\Container;

use TMT\CRM\Core\ExportImport\Application\Services\{
    ExportService,
    ImportService,
    ValidationService
};

use TMT\CRM\Core\ExportImport\Infrastructure\Persistence\{
    WpdbExportJobRepository,
    WpdbImportJobRepository,
    WpdbMappingRuleRepository
};

use TMT\CRM\Domain\Repositories\{
    ExportJobRepositoryInterface,
    ImportJobRepositoryInterface,
    MappingRuleRepositoryInterface
};

final class ExportImportServiceProvider
{
    public static function register(): void
    {
        global $wpdb;

        // Repositories
        Container::set(ExportJobRepositoryInterface::class, static fn() => new WpdbExportJobRepository($wpdb));
        Container::set(ImportJobRepositoryInterface::class, static fn() => new WpdbImportJobRepository($wpdb));
        Container::set(MappingRuleRepositoryInterface::class, static fn() => new WpdbMappingRuleRepository($wpdb));

        // Core services
        Container::set(ValidationService::class, static fn() => new ValidationService());

        Container::set(ExportService::class, static function (): ExportService {
            /** @var ExportJobRepositoryInterface $jobRepo */
            $jobRepo = Container::get(ExportJobRepositoryInterface::class);
            return new ExportService($jobRepo);
        });

        Container::set(ImportService::class, static function (): ImportService {
            /** @var ImportJobRepositoryInterface $jobRepo */
            $jobRepo = Container::get(ImportJobRepositoryInterface::class);
            /** @var ValidationService $validator */
            $validator = Container::get(ValidationService::class);
            return new ImportService($jobRepo, $validator);
        });

        // (Optional) alias nhanh nếu muốn resolve qua string:
        // Container::set('export_service', fn() => Container::get(ExportService::class));
        // Container::set('import_service', fn() => Container::get(ImportService::class));
    }
}
