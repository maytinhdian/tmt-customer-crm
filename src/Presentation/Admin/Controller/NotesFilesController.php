<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\Controller;

use TMT\CRM\Shared\Container;

final class NotesFilesController
{
    public static function register(): void
    {
        add_action('admin_post_tmt_crm_add_note', [self::class, 'handle_add_note']);
        add_action('admin_post_tmt_crm_delete_note', [self::class, 'handle_delete_note']);
        add_action('admin_post_tmt_crm_attach_file', [self::class, 'handle_attach_file']);
        add_action('admin_post_tmt_crm_detach_file', [self::class, 'handle_detach_file']);
    }

    public static function handle_add_note(): void
    {
        check_admin_referer('tmt_crm_add_note');

        $entity_type = sanitize_text_field($_POST['entity_type'] ?? '');
        $entity_id   = (int)($_POST['entity_id'] ?? 0);
        $content     = sanitize_textarea_field($_POST['content'] ?? '');
        $user_id     = get_current_user_id();

        $svc = Container::get('note-service');
        $svc->add_note($entity_type, $entity_id, $content, $user_id);

        wp_safe_redirect(wp_get_referer() ?: admin_url());
        exit;
    }

    public static function handle_delete_note(): void
    {
        check_admin_referer('tmt_crm_delete_note');

        $note_id = (int)($_POST['note_id'] ?? 0);
        $svc     = Container::get('note-service');
        $svc->delete_note($note_id);

        wp_safe_redirect(wp_get_referer() ?: admin_url());
        exit;
    }

    public static function handle_attach_file(): void
    {
        check_admin_referer('tmt_crm_attach_file');

        $entity_type   = sanitize_text_field($_POST['entity_type'] ?? '');
        $entity_id     = (int)($_POST['entity_id'] ?? 0);
        $attachment_id = (int)($_POST['attachment_id'] ?? 0);
        $user_id       = get_current_user_id();

        $svc = Container::get('file-service');
        $svc->attach_file($entity_type, $entity_id, $attachment_id, $user_id);

        wp_safe_redirect(wp_get_referer() ?: admin_url());
        exit;
    }

    public static function handle_detach_file(): void
    {
        check_admin_referer('tmt_crm_detach_file');

        $file_id = (int)($_POST['file_id'] ?? 0);
        $svc     = Container::get('file-service');
        $svc->detach_file($file_id);

        wp_safe_redirect(wp_get_referer() ?: admin_url());
        exit;
    }
}
