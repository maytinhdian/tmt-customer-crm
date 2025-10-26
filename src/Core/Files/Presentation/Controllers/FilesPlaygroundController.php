<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Files\Presentation\Controllers;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Shared\Presentation\Support\View;
use TMT\CRM\Core\Files\Domain\Repositories\FileRepositoryInterface;
use TMT\CRM\Core\Files\Application\Services\FileService;

/**
 * FilesPlaygroundController
 * - Dev playground page for Core/Files (menu under Tools)
 * - Handles upload + soft delete for a given entity_type/entity_id
 * - Renders templates/admin/core/files/playground.php via View::render_admin_module
 *
 * NOTE: Guard with WP_DEBUG to avoid exposing in production.
 */
final class FilesPlaygroundController
{
    public const SLUG = 'tmt-crm-files-playground';

    public static function bootstrap(): void
    {
        // Admin menu
        add_action('admin_menu', function () {
            if (!defined('WP_DEBUG') || !WP_DEBUG) {
                return; // dev-only
            }
            add_submenu_page(
                'tools.php',
                'TMT Files Playground',
                'Files Playground',
                'manage_options',
                self::SLUG,
                [self::class, 'render_page']
            );
        });

        // Form handlers
        add_action('admin_post_tmt_crm_playground_upload', [self::class, 'handle_upload']);
        add_action('admin_post_tmt_crm_playground_delete', [self::class, 'handle_delete']);
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Not allowed', 'tmt-crm'), 403);
        }

        $entity_type = isset($_GET['entity_type']) ? sanitize_text_field((string)$_GET['entity_type']) : 'company';
        $entity_id   = isset($_GET['entity_id']) ? (int)$_GET['entity_id'] : 1;
        $preview_id  = isset($_GET['preview']) ? (int)$_GET['preview'] : 0;

        /** @var FileRepositoryInterface $repo */
        $repo  = Container::get(FileRepositoryInterface::class);
        $files = $repo->findByEntity($entity_type, $entity_id, false);
        $selected = $preview_id > 0 && method_exists($repo, 'findById') ? $repo->findById($preview_id) : null;

        View::render_admin_module('core/files', 'playground', [
            'entity_type' => $entity_type,
            'entity_id'   => $entity_id,
            'files'       => $files,
            'selected'    => $selected,
        ]);
    }

    public static function handle_upload(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Not allowed', 'tmt-crm'), 403);
        }
        check_admin_referer('tmt_crm_playground_upload');

        $entity_type = isset($_POST['entity_type']) ? sanitize_text_field((string)$_POST['entity_type']) : '';
        $entity_id   = isset($_POST['entity_id']) ? (int)$_POST['entity_id'] : 0;

        if (!$entity_type || !$entity_id || empty($_FILES['file']['tmp_name'])) {
            wp_die(__('Bad request', 'tmt-crm'), 400);
        }

        /** @var FileService $svc */
        $svc = Container::get(FileService::class);

        $svc->storeFromUpload($_FILES['file'], $entity_type, $entity_id, get_current_user_id(), [
            'tag' => 'pg', // meta demo
        ]);

        wp_safe_redirect(add_query_arg([
            'page'        => self::SLUG,
            'entity_type' => $entity_type,
            'entity_id'   => $entity_id,
        ], admin_url('tools.php')));
        exit;
    }

    public static function handle_delete(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Not allowed', 'tmt-crm'), 403);
        }

        $file_id = isset($_GET['file_id']) ? (int)$_GET['file_id'] : 0;
        check_admin_referer('tmt_crm_playground_delete_' . $file_id);

        $entity_type = isset($_GET['entity_type']) ? sanitize_text_field((string)$_GET['entity_type']) : 'company';
        $entity_id   = isset($_GET['entity_id']) ? (int)$_GET['entity_id'] : 1;

        /** @var FileService $svc */
        $svc = Container::get(FileService::class);
        $svc->softDelete($file_id, get_current_user_id());

        wp_safe_redirect(add_query_arg([
            'page'        => self::SLUG,
            'entity_type' => $entity_type,
            'entity_id'   => $entity_id,
        ], admin_url('tools.php')));
        exit;
    }
}
