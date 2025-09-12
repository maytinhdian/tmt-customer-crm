<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\Screen;

use TMT\CRM\Presentation\Support\View;
use TMT\CRM\Shared\Container;

final class CompanyNotesFilesScreen
{
    public const MODULE   = 'notes-files';
    public const TEMPLATE = 'company-tab';

    public static function render(int $company_id): void
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
