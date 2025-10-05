<?php

declare(strict_types=1);

namespace TMT\CRM\Core\ExportImport\Infrastructure\Providers;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\ExportImport\Application\Services\{ExportService, ImportService, ValidationService};
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

/**
 * Đăng ký binding cho module Export/Import vào Container
 */
final class ExportImportServiceProvider
{
    public static function register(): void
    {
        global $wpdb;

        // Repositories (bind interface -> implementation)
        Container::set(ExportJobRepositoryInterface::class, function () use ($wpdb) {
            return new WpdbExportJobRepository($wpdb);
        });
        Container::set(ImportJobRepositoryInterface::class, function () use ($wpdb) {
            return new WpdbImportJobRepository($wpdb);
        });
        Container::set(MappingRuleRepositoryInterface::class, function () use ($wpdb) {
            return new WpdbMappingRuleRepository($wpdb);
        });

        // Core services
        Container::set(ValidationService::class, function () {
            return new ValidationService();
        });

        Container::set(ExportService::class, function () {
            /** @var ExportJobRepositoryInterface $jobRepo */
            $jobRepo = Container::get(ExportJobRepositoryInterface::class);
            return new ExportService($jobRepo);
        });

        Container::set(ImportService::class, function () {
            /** @var ImportJobRepositoryInterface $jobRepo */
            $jobRepo = Container::get(ImportJobRepositoryInterface::class);
            /** @var ValidationService $validator */
            $validator = Container::get(ValidationService::class);
            return new ImportService($jobRepo, $validator);
        });

        // Optional aliases (nếu bạn muốn resolve nhanh qua key string)
        // $c->set('export_service', fn(Container $c) => $c->get(ExportService::class));
        // $c->set('import_service', fn(Container $c) => $c->get(ImportService::class));
    }
}
