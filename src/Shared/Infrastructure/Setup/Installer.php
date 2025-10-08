<?php

declare(strict_types=1);

namespace TMT\CRM\Shared\Infrastructure\Setup;

use TMT\CRM\Shared\Infrastructure\Setup\Migration\SchemaMigratorInterface;

/**
 * Installer điều phối schema theo từng module migrator.
 * - register() gắn hook kích hoạt + auto-upgrade khi plugin load (file chính).
 * - on_activate(): chạy install cho tất cả migrator + ghi version.
 * - maybe_upgrade(): so sánh version và gọi upgrade khi cần.
 */
final class Installer
{


    /** Bootstrap (file chính) */
    public static function register(): void
    {
        // Đăng ký activation hook 1 lần duy nhất tại plugin main
        register_activation_hook(TMT_CRM_FILE, [self::class, 'on_activate']);

        // Tự kiểm tra nâng cấp khi plugin load (ưu tiên mức 5 đủ sớm)
        add_action('plugins_loaded', [self::class, 'maybe_upgrade'], 5);
    }

    /**
     * Danh sách migrator theo thứ tự mong muốn (ưu tiên core trước, rồi modules).
     * Lưu ý: class phải tồn tại & implements SchemaMigratorInterface.
     *
     * @return array<class-string<SchemaMigratorInterface>>
     */
    private static function migrators(): array
    {
        return [
            // Core trước
            \TMT\CRM\Core\Events\Infrastructure\Migrations\EventsMigrator::class,
            \TMT\CRM\Core\Log\Infrastructure\Migrations\LogMigrator::class,
            \TMT\CRM\Core\Numbering\Infrastructure\Migrations\NumberingMigrator::class,
            \TMT\CRM\Core\Notifications\Infrastructure\Migrations\NotificationsMigrator::class,
            \TMT\CRM\Core\ExportImport\Infrastructure\Migrations\ExportImportMigrator::class,
            \TMT\CRM\Core\Files\Infrastructure\Migrations\FileMigrator::class,

            // Modules nghiệp vụ
            \TMT\CRM\Modules\Company\Infrastructure\Migrations\CompanyMigrator::class,
            \TMT\CRM\Modules\Customer\Infrastructure\Migrations\CustomerMigrator::class,
            \TMT\CRM\Modules\Contact\Infrastructure\Migrations\ContactMigrator::class,
            \TMT\CRM\Modules\Note\Infrastructure\Migrations\NoteMigrator::class,
            \TMT\CRM\Modules\Quotation\Infrastructure\Migrations\QuoteMigrator::class,
            \TMT\CRM\Modules\Password\Infrastructure\Migrations\PasswordMigrator::class,
            \TMT\CRM\Modules\License\Infrastructure\Migrations\LicenseMigrator::class,

            // Tương lai:
            // \TMT\CRM\Modules\Order\Infrastructure\Persistence\Migration\OrderMigrator::class,
            // \TMT\CRM\Modules\Invoice\Infrastructure\Persistence\Migration\InvoiceMigrator::class,
            // \TMT\CRM\Modules\Payment\Infrastructure\Persistence\Migration\PaymentMigrator::class,
            // \TMT\CRM\Modules\Sequence\Infrastructure\Persistence\Migration\SequenceMigrator::class,
        ];
    }

    /** Kích hoạt plugin: migrate schema & set version */
    public function on_activate(): void
    {
    

        foreach (self::migrators() as $migrator_class) {
            if (!class_exists($migrator_class)) {
                // Ghi log để dev biết thiếu file/namespace
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[TMT CRM][Installer] Migrator class not found: {$migrator_class}");
                }
                continue;
            }

            /** @var SchemaMigratorInterface $migrator */
            $migrator = new $migrator_class();
            $migrator->install();

            // Ghi version mục tiêu sau khi install
            self::set_module_version($migrator_class::module_key(), $migrator_class::target_version());
        }

        // Ghi global DB version một chỗ duy nhất (file chính)
        update_option('tmt_crm_db_version', TMT_CRM_DB_VERSION, true);
    }

    /** Tự động nâng cấp khi phát hiện lệch version */
    public static function maybe_upgrade(): void
    {
        foreach (self::migrators() as $migrator_class) {
            if (!class_exists($migrator_class)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[TMT CRM][Installer] Migrator class not found (upgrade skipped): {$migrator_class}");
                }
                continue;
            }

            /** @var SchemaMigratorInterface $migrator */
            $migrator = new $migrator_class();

            $module_key = $migrator_class::module_key();
            $option_key = self::module_option_key($module_key);

            $from = (string) get_option($option_key, '');
            $to   = (string) $migrator_class::target_version();

            if ($from !== $to) {
                // Thực hiện nâng cấp từ version cũ lên target
                $migrator->upgrade($from);

                // Cập nhật mốc version của module về target
                self::set_module_version($module_key, $to);
            }
        }

        // Đồng bộ global DB version (không bắt buộc nhưng nhất quán)
        if ((string) get_option('tmt_crm_db_version', '') !== (string) TMT_CRM_DB_VERSION) {
            update_option('tmt_crm_db_version', TMT_CRM_DB_VERSION, true);
        }
    }

    // ------------------------
    // Helpers
    // ------------------------

    private static function module_option_key(string $module_key): string
    {
        return 'tmt_crm_schema_' . $module_key . '_ver';
    }

    private static function set_module_version(string $module_key, string $version): void
    {
        update_option(self::module_option_key($module_key), $version, true);
    }
}
