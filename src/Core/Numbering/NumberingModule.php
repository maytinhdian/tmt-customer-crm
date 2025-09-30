<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Numbering;

use TMT\CRM\Core\Numbering\Infrastructure\Installer;
use TMT\CRM\Core\Numbering\Infrastructure\Providers\NumberingServiceProvider;
use TMT\CRM\Core\Numbering\Presentation\Admin\Settings\NumberingSettingsIntegration;

/**
 * Module Core/Numbering - bootstrap (file chính)
 * - Đăng ký cài đặt/migrate DB
 * - Nạp integration vào Settings
 * - Khởi tạo Service Provider (DI)
 */
final class NumberingModule
{
    public const VERSION = '1.0.0';
    public const OPTION_VERSION = 'tmt_crm_core_numbering_version';

    /** Gọi 1 lần ở bootstrap (file chính) */
    public static function register(): void
    {
        add_action('plugins_loaded', [self::class, 'bootstrap'], 5);
    }

    /** Khởi động module */
    public static function bootstrap(): void
    {
        // 1) Cài đặt/migrate
        // add_action('init', [Installer::class, 'maybe_install'], 1);

        // 2) Đăng ký DI bindings
        NumberingServiceProvider::register();

        // 3) Tích hợp vào Core/Settings (tab "Đánh số tự động")
        NumberingSettingsIntegration::register();
    }
}
