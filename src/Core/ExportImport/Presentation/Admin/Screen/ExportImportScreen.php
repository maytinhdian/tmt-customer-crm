<?php

declare(strict_types=1);

namespace TMT\CRM\Core\ExportImport\Presentation\Admin\Screen;

use TMT\CRM\Shared\Presentation\Support\View;
use TMT\CRM\Core\Capabilities\Domain\Capability;

final class ExportImportScreen
{
    public const PAGE_SLUG = 'tmt-crm-export-import';

    public static function register_menu(): void
    {
        add_submenu_page(
            'tmt-crm',
            'Export / Import',
            'Export / Import',
            Capability::COMPANY_CREATE,
            self::PAGE_SLUG,
            [self::class, 'render']
        );
    }

    public static function render(): void
    {
        View::render_admin_module('core/export-import', 'index', [
            'title' => 'Export / Import'
        ]);
    }
}
