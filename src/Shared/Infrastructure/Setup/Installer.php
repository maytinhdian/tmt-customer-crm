<?php

declare(strict_types=1);

namespace TMT\CRM\Shared\Infrastructure\Setup;

use TMT\CRM\Shared\Infrastructure\Setup\Migration\SchemaMigratorInterface;

final class Installer
{
    /** Bootstrap (file chính) */
    public static function register(): void
    {
        register_activation_hook(TMT_CRM_FILE, [self::class, 'on_activate']);
        add_action('plugins_loaded', [self::class, 'maybe_upgrade'], 5);
    }

    private static function migrators(): array
    {
        return [
            // \TMT\CRM\Modules\Customer\Infrastructure\Persistence\Migration\CustomerMigrator::class,
            // \TMT\CRM\Modules\Company\Infrastructure\Persistence\Migration\CompanyMigrator::class,
            // \TMT\CRM\Modules\Contact\Infrastructure\Persistence\Migration\ContactMigrator::class,
            \TMT\CRM\Modules\Note\Infrastructure\Migration\NoteMigrator::class,
            // \TMT\CRM\Modules\Sequence\Infrastructure\Persistence\Migration\SequenceMigrator::class,
            // \TMT\CRM\Modules\Quote\Infrastructure\Persistence\Migration\QuoteMigrator::class,
            // \TMT\CRM\Modules\Order\Infrastructure\Persistence\Migration\OrderMigrator::class,
            // \TMT\CRM\Modules\Invoice\Infrastructure\Persistence\Migration\InvoiceMigrator::class,
            // \TMT\CRM\Modules\Payment\Infrastructure\Persistence\Migration\PaymentMigrator::class,
        ];
    }

    public static function on_activate(): void
    {
        foreach (self::migrators() as $migrator_class) {
            /** @var SchemaMigratorInterface $migrator */
            $migrator = new $migrator_class();
            $migrator->install();
        }
    }

    public static function maybe_upgrade(): void
    {
        foreach (self::migrators() as $migrator_class) {
            /** @var SchemaMigratorInterface $migrator */
            $migrator = new $migrator_class();
            $from = (string) get_option('tmt_crm_schema_' . $migrator_class::module_key() . '_ver', '');
            $to   = $migrator_class::target_version();
            if ($from !== $to) {
                $migrator->upgrade($from);
            }
        }
    }
}
