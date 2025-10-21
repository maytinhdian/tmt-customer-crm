<?php

/**
 * ViewController
 * ---------------
 * Hiển thị file trực tiếp (inline) cho ảnh hoặc PDF.
 * Được gọi qua: admin-post.php?action=tmt_crm_view_file&file_id={id}&_wpnonce={nonce}
 */

declare(strict_types=1);

namespace TMT\CRM\Core\Files\Presentation\Controllers;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Files\Domain\Repositories\FileRepositoryInterface;
use TMT\CRM\Core\Files\Domain\Contracts\StorageInterface;
use TMT\CRM\Core\Files\Application\Services\PolicyService;

final class ViewController
{
    /**
     * Đăng ký hook admin_post
     */
    public static function bootstrap(): void
    {
        add_action('admin_post_tmt_crm_view_file', [self::class, 'handle']);
    }

    /**
     * Xử lý hiển thị file (inline)
     */
    public static function handle(): void
    {
        $file_id = isset($_GET['file_id']) ? (int) $_GET['file_id'] : 0;
        $nonce   = isset($_GET['_wpnonce']) ? (string) $_GET['_wpnonce'] : '';

        if (!$file_id || !wp_verify_nonce($nonce, 'tmt_crm_view_file_' . $file_id)) {
            wp_die(__('Invalid request', 'tmt-crm'), 400);
        }

        /** @var FileRepositoryInterface $repo */
        $repo = Container::get(FileRepositoryInterface::class);
        $dto  = $repo->findById($file_id);
        if (!$dto) {
            wp_die(__('File not found', 'tmt-crm'), 404);
        }

        // Kiểm tra quyền xem
        $user_id = get_current_user_id();
        if (!PolicyService::canRead($user_id, $dto)) {
            wp_die(__('You do not have permission to view this file.', 'tmt-crm'), 403);
        }

        /** @var StorageInterface $storage */
        $storage = Container::get(StorageInterface::class);
        $stream  = $storage->read($dto->path);

        if (!$stream) {
            wp_die(__('Cannot read file', 'tmt-crm'), 500);
        }

        // ===== GỬI HEADER INLINE =====
        nocache_headers();

        $mime = $dto->mime ?: 'application/octet-stream';
        $filename = $dto->originalName ?: ('file-' . $dto->id);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($filename) . '"');
        header('Cache-Control: private, max-age=31536000');

        // Đọc nội dung vật lý
        $upload_dir = wp_upload_dir();
        $abs = trailingslashit($upload_dir['basedir']) . ltrim($dto->path, '/');

        if (is_file($abs)) {
            header('Content-Length: ' . filesize($abs));
            readfile($abs);
        } elseif (is_resource($stream)) {
            fpassthru($stream);
            fclose($stream);
        } else {
            echo (string) $stream;
        }

        exit;
    }
}
