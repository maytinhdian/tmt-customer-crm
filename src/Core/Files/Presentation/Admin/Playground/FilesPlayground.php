<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Files\Presentation\Admin\Playground;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Files\Application\Services\FileService;
use TMT\CRM\Core\Files\Domain\Repositories\FileRepositoryInterface;

final class FilesPlayground
{
    public static function bootstrap(): void
    {
        // Trang menu
        add_action('admin_menu', function () {
            add_submenu_page(
                'tools.php',
                'TMT Files Playground',
                'TMT Files Playground',
                'manage_options',
                'tmt-crm-files-playground',
                [self::class, 'render_page']
            );
        });

        // Handle upload
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

        echo '<div class="wrap"><h1>TMT Files Playground</h1>';

        // Upload form
        echo '<h2>Upload</h2>';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" enctype="multipart/form-data">';
        wp_nonce_field('tmt_crm_playground_upload');
        echo '<input type="hidden" name="action" value="tmt_crm_playground_upload" />';
        echo '<table class="form-table"><tbody>';
        echo '<tr><th>Entity Type</th><td><input type="text" name="entity_type" value="'.esc_attr($entity_type).'" class="regular-text" /></td></tr>';
        echo '<tr><th>Entity ID</th><td><input type="number" name="entity_id" value="'.esc_attr((string)$entity_id).'" class="small-text" /></td></tr>';
        echo '<tr><th>File</th><td><input type="file" name="file" required /></td></tr>';
        echo '</tbody></table>';
        submit_button('Upload file');
        echo '</form>';

        // List files
        /** @var FileRepositoryInterface $repo */
        $repo = Container::get(FileRepositoryInterface::class);
        $files = $repo->findByEntity($entity_type, $entity_id, false);

        echo '<h2>Files of '.esc_html($entity_type).' #'.esc_html((string)$entity_id).'</h2>';
        if (!$files) {
            echo '<p><em>No files.</em></p>';
        } else {
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th>ID</th><th>Name</th><th>MIME</th><th>Size</th><th>Uploaded At</th><th>Actions</th>';
            echo '</tr></thead><tbody>';
            foreach ($files as $f) {
                $download_url = wp_nonce_url(
                    admin_url('admin-post.php?action=tmt_crm_download_file&file_id='.$f->id),
                    'tmt_crm_download_file_'.$f->id
                );
                $delete_url = wp_nonce_url(
                    admin_url('admin-post.php?action=tmt_crm_playground_delete&file_id='.$f->id.'&entity_type='.$entity_type.'&entity_id='.$entity_id),
                    'tmt_crm_playground_delete_'.$f->id
                );
                echo '<tr>';
                echo '<td>'.esc_html((string)$f->id).'</td>';
                echo '<td>'.esc_html($f->originalName).'</td>';
                echo '<td>'.esc_html($f->mime).'</td>';
                echo '<td>'.esc_html(size_format($f->sizeBytes)).'</td>';
                echo '<td>'.esc_html($f->uploadedAt).'</td>';
                echo '<td>';
                echo '<a class="button" href="'.esc_url($download_url).'">Download</a> ';
                echo '<a class="button button-link-delete" href="'.esc_url($delete_url).'" onclick="return confirm(\'Delete this file?\')">Delete</a>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '</div>';
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
        $currentUserId = get_current_user_id();

        // Lưu file
        $svc->storeFromUpload($_FILES['file'], $entity_type, $entity_id, $currentUserId);

        wp_safe_redirect(add_query_arg([
            'page' => 'tmt-crm-files-playground',
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
        ], admin_url('tools.php')));
        exit;
    }

    public static function handle_delete(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Not allowed', 'tmt-crm'), 403);
        }
        $file_id = isset($_GET['file_id']) ? (int)$_GET['file_id'] : 0;
        check_admin_referer('tmt_crm_playground_delete_'.$file_id);

        $entity_type = isset($_GET['entity_type']) ? sanitize_text_field((string)$_GET['entity_type']) : 'company';
        $entity_id   = isset($_GET['entity_id']) ? (int)$_GET['entity_id'] : 1;

        /** @var FileService $svc */
        $svc = Container::get(FileService::class);
        $svc->softDelete($file_id, get_current_user_id());

        wp_safe_redirect(add_query_arg([
            'page' => 'tmt-crm-files-playground',
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
        ], admin_url('tools.php')));
        exit;
    }
}
