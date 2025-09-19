<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Presentation\Admin\Controller;

final class NotificationController
{
    public static function render_index(): void
    {
        // View::render_admin_module('notifications/index', [...]);
    }

    public static function ajax_mark_read(): void
    {
        // Xử lý mark read theo delivery_id + current_user
    }
}
