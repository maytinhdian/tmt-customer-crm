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
            \TMT\CRM\Modules\Customer\Infrastructure\Migrations\CustomerMigrator::class,
            \TMT\CRM\Modules\Company\Infrastructure\Migrations\CompanyMigrator::class,
            \TMT\CRM\Modules\Contact\Infrastructure\Migrations\ContactMigrator::class,
            \TMT\CRM\Modules\Note\Infrastructure\Migrations\NoteMigrator::class,
            // \TMT\CRM\Modules\Sequence\Infrastructure\Persistence\Migration\SequenceMigrator::class,
            \TMT\CRM\Modules\Quotation\Infrastructure\Migrations\QuoteMigrator::class,
            \TMT\CRM\Core\Notifications\Infrastructure\Migrations\NotificationsMigrator::class,
            \TMT\CRM\Modules\Password\Infrastructure\Migrations\PasswordMigrator::class,
            \TMT\CRM\Core\Numbering\Infrastructure\Migrations\NumberingMigrator::class,
            // \TMT\CRM\Core\Events\Infrastructure\Migrations\EventsMigrator::class,
            \TMT\CRM\Modules\License\Infrastructure\Migrations\LicenseMigrator::class,
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
