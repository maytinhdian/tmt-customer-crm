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

    // public static function render_page(): void
    // {
    //     if (!current_user_can('manage_options')) {
    //         wp_die(__('Not allowed', 'tmt-crm'), 403);
    //     }

    //     $entity_type = isset($_GET['entity_type']) ? sanitize_text_field((string)$_GET['entity_type']) : 'company';
    //     $entity_id   = isset($_GET['entity_id']) ? (int)$_GET['entity_id'] : 1;

    //     echo '<div class="wrap"><h1>TMT Files Playground</h1>';

    //     // Upload form
    //     echo '<h2>Upload</h2>';
    //     echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" enctype="multipart/form-data">';
    //     wp_nonce_field('tmt_crm_playground_upload');
    //     echo '<input type="hidden" name="action" value="tmt_crm_playground_upload" />';
    //     echo '<table class="form-table"><tbody>';
    //     echo '<tr><th>Entity Type</th><td><input type="text" name="entity_type" value="'.esc_attr($entity_type).'" class="regular-text" /></td></tr>';
    //     echo '<tr><th>Entity ID</th><td><input type="number" name="entity_id" value="'.esc_attr((string)$entity_id).'" class="small-text" /></td></tr>';
    //     echo '<tr><th>File</th><td><input type="file" name="file" required /></td></tr>';
    //     echo '</tbody></table>';
    //     submit_button('Upload file');
    //     echo '</form>';

    //     // List files
    //     /** @var FileRepositoryInterface $repo */
    //     $repo = Container::get(FileRepositoryInterface::class);
    //     $files = $repo->findByEntity($entity_type, $entity_id, false);

    //     echo '<h2>Files of '.esc_html($entity_type).' #'.esc_html((string)$entity_id).'</h2>';
    //     if (!$files) {
    //         echo '<p><em>No files.</em></p>';
    //     } else {
    //         echo '<table class="widefat striped"><thead><tr>';
    //         echo '<th>ID</th><th>Name</th><th>MIME</th><th>Size</th><th>Uploaded At</th><th>Actions</th>';
    //         echo '</tr></thead><tbody>';
    //         foreach ($files as $f) {
    //             $download_url = wp_nonce_url(
    //                 admin_url('admin-post.php?action=tmt_crm_download_file&file_id='.$f->id),
    //                 'tmt_crm_download_file_'.$f->id
    //             );
    //             $delete_url = wp_nonce_url(
    //                 admin_url('admin-post.php?action=tmt_crm_playground_delete&file_id='.$f->id.'&entity_type='.$entity_type.'&entity_id='.$entity_id),
    //                 'tmt_crm_playground_delete_'.$f->id
    //             );
    //             echo '<tr>';
    //             echo '<td>'.esc_html((string)$f->id).'</td>';
    //             echo '<td>'.esc_html($f->originalName).'</td>';
    //             echo '<td>'.esc_html($f->mime).'</td>';
    //             echo '<td>'.esc_html(size_format($f->sizeBytes)).'</td>';
    //             echo '<td>'.esc_html($f->uploadedAt).'</td>';
    //             echo '<td>';
    //             echo '<a class="button" href="'.esc_url($download_url).'">Download</a> ';
    //             echo '<a class="button button-link-delete" href="'.esc_url($delete_url).'" onclick="return confirm(\'Delete this file?\')">Delete</a>';
    //             echo '</td>';
    //             echo '</tr>';
    //         }
    //         echo '</tbody></table>';
    //     }

    //     echo '</div>';
    // }
    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Not allowed', 'tmt-crm'), 403);
        }

        // (Khuyến nghị) chỉ bật playground ở môi trường dev
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            echo '<div class="wrap"><h1>TMT Files Playground</h1><p>Playground is disabled. Enable WP_DEBUG to use.</p></div>';
            return;
        }

        $entity_type = isset($_GET['entity_type']) ? sanitize_text_field((string)$_GET['entity_type']) : 'company';
        $entity_id   = isset($_GET['entity_id']) ? (int)$_GET['entity_id'] : 1;

        // File được chọn để preview trong trang
        $preview_id = isset($_GET['preview']) ? (int)$_GET['preview'] : 0;

        echo '<div class="wrap"><h1>TMT Files Playground</h1>';

        // ====== KHỐI PREVIEW TRONG TRANG (nếu có chọn) ======
        if ($preview_id > 0) {
            /** @var FileRepositoryInterface $repo */
            $repo = Container::get(FileRepositoryInterface::class);
            $selected = method_exists($repo, 'findById') ? $repo->findById($preview_id) : null;

            if ($selected) {
                $is_image = strpos((string)$selected->mime, 'image/') === 0;
                $is_pdf   = (string)$selected->mime === 'application/pdf' || preg_match('~\.pdf$~i', (string)$selected->originalName);

                $view_url = wp_nonce_url(
                    admin_url('admin-post.php?action=tmt_crm_view_file&file_id=' . (int)$selected->id),
                    'tmt_crm_view_file_' . (int)$selected->id
                );
                $download_url = wp_nonce_url(
                    admin_url('admin-post.php?action=tmt_crm_download_file&file_id=' . (int)$selected->id),
                    'tmt_crm_download_file_' . (int)$selected->id
                );

                echo '<div class="card" style="padding:16px;margin:16px 0; max-width:100vw;">';
                echo '<h2 style="margin-top:0;">Preview — ID #' . esc_html((string)$selected->id) . '</h2>';
                echo '<div style="display:flex;gap:16px;align-items:flex-start;">';

                // Media
                echo '<div style="flex:1 1 auto;min-height:320px;background:#fafafa;border:1px dashed #ddd;border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;">';
                if ($is_image) {
                    echo '<img src="' . esc_url($view_url) . '" alt="" style="max-width:100%;max-height:80vh;">';
                } elseif ($is_pdf) {
                    echo '<iframe src="' . esc_url($view_url) . '" style="width:100%;height:75vh;" frameborder="0"></iframe>';
                } else {
                    echo '<div style="text-align:center;padding:24px;">';
                    echo '<p><strong>' . esc_html($selected->mime ?: 'application/octet-stream') . '</strong></p>';
                    echo '<p>' . esc_html__('Loại tệp này không hỗ trợ xem trực tiếp. Bạn có thể tải xuống.', 'tmt-crm') . '</p>';
                    echo '</div>';
                }
                echo '</div>';

                // Meta
                echo '<div style="min-width:280px">';
                echo '<table class="widefat striped"><tbody>';
                echo '<tr><th>' . esc_html__('Original name', 'tmt-crm') . '</th><td>' . esc_html($selected->originalName) . '</td></tr>';
                echo '<tr><th>' . esc_html__('MIME', 'tmt-crm') . '</th><td>' . esc_html($selected->mime ?: '') . '</td></tr>';
                echo '<tr><th>' . esc_html__('Size', 'tmt-crm') . '</th><td>' . esc_html(size_format((float)$selected->sizeBytes)) . '</td></tr>';
                echo '<tr><th>' . esc_html__('Entity', 'tmt-crm') . '</th><td>' . esc_html($selected->entityType . '#' . $selected->entityId) . '</td></tr>';
                echo '<tr><th>' . esc_html__('Storage path', 'tmt-crm') . '</th><td><code>' . esc_html($selected->path) . '</code></td></tr>';
                echo '<tr><th>' . esc_html__('Uploaded at', 'tmt-crm') . '</th><td>' . esc_html($selected->uploadedAt ?: '') . '</td></tr>';
                echo '<tr><th>' . esc_html__('Uploaded by', 'tmt-crm') . '</th><td>' . (int)$selected->uploadedBy . '</td></tr>';
                echo '</tbody></table>';

                echo '<p style="margin-top:8px;">';
                echo '<a class="button" href="' . esc_url($view_url) . '" target="_blank">' . esc_html__('Mở tab mới', 'tmt-crm') . '</a> ';
                echo '<a class="button button-primary" href="' . esc_url($download_url) . '">' . esc_html__('Tải xuống', 'tmt-crm') . '</a> ';
                $back = add_query_arg([
                    'page'        => 'tmt-crm-files-playground',
                    'entity_type' => $entity_type,
                    'entity_id'   => $entity_id,
                ], admin_url('tools.php'));
                echo '<a class="button" href="' . esc_url($back) . '">' . esc_html__('Đóng xem', 'tmt-crm') . '</a>';
                echo '</p>';

                echo '</div>'; // meta
                echo '</div>'; // card
            }
        }

        // ====== FORM UPLOAD ======
        echo '<h2>Upload</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" enctype="multipart/form-data">';
        wp_nonce_field('tmt_crm_playground_upload');
        echo '<input type="hidden" name="action" value="tmt_crm_playground_upload" />';
        echo '<table class="form-table"><tbody>';
        echo '<tr><th>Entity Type</th><td><input type="text" name="entity_type" value="' . esc_attr($entity_type) . '" class="regular-text" /></td></tr>';
        echo '<tr><th>Entity ID</th><td><input type="number" name="entity_id" value="' . esc_attr((string)$entity_id) . '" class="small-text" min="1" /></td></tr>';
        echo '<tr><th>File</th><td><input type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.pdf" required /></td></tr>';
        echo '</tbody></table>';
        submit_button('Upload file');
        echo '</form>';

        // ====== DANH SÁCH FILES ======
        /** @var FileRepositoryInterface $repo */
        $repo = Container::get(FileRepositoryInterface::class);
        $files = $repo->findByEntity($entity_type, $entity_id, false);

        echo '<h2>Files of ' . esc_html($entity_type) . ' #' . esc_html((string)$entity_id) . '</h2>';
        if (!$files) {
            echo '<p><em>No files.</em></p>';
        } else {
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th>ID</th><th>Name</th><th>MIME</th><th>Size</th><th>Uploaded At</th><th>Actions</th>';
            echo '</tr></thead><tbody>';

            foreach ($files as $f) {
                $download_url = wp_nonce_url(
                    admin_url('admin-post.php?action=tmt_crm_download_file&file_id=' . $f->id),
                    'tmt_crm_download_file_' . $f->id
                );
                $inline_view_url = wp_nonce_url(
                    admin_url('admin-post.php?action=tmt_crm_view_file&file_id=' . $f->id),
                    'tmt_crm_view_file_' . $f->id
                );
                $inpage_url = add_query_arg([
                    'page'        => 'tmt-crm-files-playground',
                    'entity_type' => $entity_type,
                    'entity_id'   => $entity_id,
                    'preview'     => $f->id,
                ], admin_url('tools.php'));
                $delete_url = wp_nonce_url(
                    admin_url('admin-post.php?action=tmt_crm_playground_delete&file_id=' . $f->id . '&entity_type=' . $entity_type . '&entity_id=' . $entity_id),
                    'tmt_crm_playground_delete_' . $f->id
                );

                echo '<tr>';
                echo '<td>' . esc_html((string)$f->id) . '</td>';
                echo '<td>' . esc_html($f->originalName) . '</td>';
                echo '<td>' . esc_html($f->mime) . '</td>';
                echo '<td>' . esc_html(size_format($f->sizeBytes)) . '</td>';
                echo '<td>' . esc_html($f->uploadedAt) . '</td>';
                echo '<td>';
                echo '<a class="button button-small" href="' . esc_url($inpage_url) . '">' . esc_html__('Xem trong trang', 'tmt-crm') . '</a> ';
                echo '<a class="button button-small" href="' . esc_url($inline_view_url) . '" target="_blank">' . esc_html__('Xem tab mới', 'tmt-crm') . '</a> ';
                echo '<a class="button button-small" href="' . esc_url($download_url) . '">' . esc_html__('Tải', 'tmt-crm') . '</a> ';
                echo '<a class="button button-small button-link-delete" href="' . esc_url($delete_url) . '" onclick="return confirm(\'Delete this file?\')">' . esc_html__('Xoá', 'tmt-crm') . '</a>';
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        echo '</div>'; // .wrap
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
        check_admin_referer('tmt_crm_playground_delete_' . $file_id);

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
