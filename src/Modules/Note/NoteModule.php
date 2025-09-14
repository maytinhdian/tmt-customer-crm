<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Note;

use TMT\CRM\Shared\Container;
use TMT\CRM\Modules\Note\Application\Services\{FileService,NoteService};
use TMT\CRM\Modules\Note\Presentation\Admin\Controller\NotesFilesController;
use TMT\CRM\Modules\Note\Domain\Repositories\{NoteRepositoryInterface, FileRepositoryInterface};
use TMT\CRM\Modules\Note\Infrastructure\Persistence\{WpdbNoteRepository, WpdbFileRepository};

final class NoteModule
{
    public static function register(): void
    {
        //---------------------
        // Bind theo Interface
        //---------------------
        Container::set(NoteRepositoryInterface::class,            fn() => new WpdbNoteRepository($GLOBALS['wpdb']));
        Container::set(FileRepositoryInterface::class,            fn() => new WpdbFileRepository($GLOBALS['wpdb']));

        // Container wiring
        Container::set('note-repo',       fn() =>     Container::get(NoteRepositoryInterface::class));
        Container::set('file-repo',            fn() => Container::get(FileRepositoryInterface::class));

        Container::set('note-service', fn() => new NoteService(Container::get('note-repo')));
        Container::set('file-service', fn() => new FileService(Container::get('file-repo')));
        add_action('admin_init', function () {
            NotesFilesController::register();
        });
    }
}
