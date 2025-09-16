<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Note\Presentation\Admin\Screen;

use TMT\CRM\Shared\Presentation\Support\View;
use TMT\CRM\Shared\Container\Container;

final class CompanyNotesFilesScreen
{
    public const PAGE_SLUG = 'tmt_crm_notes_files';
    public const MODULE   = 'notes-files';
    public const TEMPLATE = 'company-tabs';
    public static function on_load_notes(): void {}
    public static function dispatch(int $company_id): void
    {
        $note_svc = Container::get('note-service');
        $file_svc = Container::get('file-service');

        View::render_admin_module(self::MODULE, self::TEMPLATE, [
            'entity_type' => 'company',
            'entity_id'   => (int)$company_id,
            'notes'       => $note_svc->list_notes('company', $company_id),
            'files'       => $file_svc->list_files('company', $company_id),
        ]);
    }
}
