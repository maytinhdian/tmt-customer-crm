<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Note\Presentation\Admin\Screen;

use TMT\CRM\Presentation\Support\View;
use TMT\CRM\Shared\Container;

final class CustomerNotesFilesScreen
{
    public const MODULE   = 'notes-files';
    public const TEMPLATE = 'customer-tab';

    public static function render(int $customer_id): void
    {
        $note_svc = Container::get('note-service');
        $file_svc = Container::get('file-service');

        View::render_admin_module(self::MODULE, self::TEMPLATE, [
            'entity_type' => 'customer',
            'entity_id'   => (int)$customer_id,
            'notes'       => $note_svc->list_notes('customer', $customer_id),
            'files'       => $file_svc->list_files('customer', $customer_id),
        ]);
    }
}
