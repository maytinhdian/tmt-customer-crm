<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Files\Presentation\Controllers;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Files\Application\Services\FileService;

final class DownloadController
{
    public static function bootstrap(): void
    {
        add_action('admin_post_tmt_crm_download_file', [self::class, 'handle']);
        add_action('admin_post_nopriv_tmt_crm_download_file', [self::class, 'handle']);
    }

    // public static function handle(): void
    // {
    //     $fileId = isset($_GET['file_id']) ? (int)$_GET['file_id'] : 0;
    //     $nonce  = isset($_GET['_wpnonce']) ? (string)$_GET['_wpnonce'] : '';

    //     if (!$fileId || !wp_verify_nonce($nonce, 'tmt_crm_download_file_' . $fileId)) {
    //         wp_die(__('Invalid request', 'tmt-crm'), 400);
    //     }

    //     $currentUserId = get_current_user_id();
    //     /** @var FileService $svc */
    //     $svc = Container::get(FileService::class);

    //     $stream = $svc->download($fileId, $currentUserId);
    //     if (is_wp_error($stream)) {
    //         wp_die(esc_html($stream->get_error_message()), (int)($stream->get_error_data()['status'] ?? 400));
    //     }

    //     // Fetch file meta for headers:
    //     $file = $svc->list('', 0); // Not ideal to fetch; better expose a `getById` in service.
    //     // To keep things simple, re-read via repo:
    //     $repo = \TMT\CRM\Shared\Container\Container::get(\TMT\CRM\Core\Files\Domain\Repositories\FileRepositoryInterface::class);
    //     $dto  = $repo->findById($fileId);
    //     if (!$dto) {
    //         wp_die(__('File not found', 'tmt-crm'), 404);
    //     }

    //     nocache_headers();
    //     // Chuẩn bị tên file an toàn + hỗ trợ Unicode
    //     $filename_original = (string)$dto->originalName;
    //     $filename_ascii    = sanitize_file_name(remove_accents($filename_original)); // fallback ASCII
    //     $filename_utf8     = $filename_original !== '' ? $filename_original : $filename_ascii;

    //     // Tránh header injection (loại CR/LF)
    //     $filename_ascii = str_replace(["\r", "\n"], '', $filename_ascii);
    //     $filename_utf8  = str_replace(["\r", "\n"], '', $filename_utf8);

    //     header('Content-Type: ' . $dto->mime);
    //     header('Content-Length: ' . (string)$dto->sizeBytes);

    //     // Cả filename (ASCII) và filename* (UTF-8) theo RFC5987
    //     header(
    //         'Content-Disposition: attachment; ' .
    //             'filename="' . wp_basename($filename_ascii) . '"; ' .
    //             "filename*=UTF-8''" . rawurlencode($filename_utf8)
    //     );

    //     if (is_resource($stream)) {
    //         fpassthru($stream);
    //         if (is_resource($stream)) {
    //             fclose($stream);
    //         }
    //     } else {
    //         echo (string)$stream;
    //     }
    //     exit;
    // }
    public static function handle(): void
    {
        $fileId = isset($_GET['file_id']) ? (int)$_GET['file_id'] : 0;
        $nonce  = isset($_GET['_wpnonce']) ? (string)$_GET['_wpnonce'] : '';

        if (!$fileId || !wp_verify_nonce($nonce, 'tmt_crm_download_file_' . $fileId)) {
            wp_die(__('Invalid request', 'tmt-crm'), 400);
        }

        $currentUserId = get_current_user_id();

        /** @var \TMT\CRM\Core\Files\Application\Services\FileService $svc */
        $svc = \TMT\CRM\Shared\Container\Container::get(\TMT\CRM\Core\Files\Application\Services\FileService::class);

        // 1) Lấy stream (đã kèm kiểm tra Policy bên trong service)
        $stream = $svc->download($fileId, $currentUserId);
        if (is_wp_error($stream)) {
            wp_die(esc_html($stream->get_error_message()), (int)($stream->get_error_data()['status'] ?? 400));
        }

        // 2) Lấy metadata đúng file (đừng gọi $svc->list('', 0) nữa)
        $dto = $svc->getById($fileId);                 // <-- cần thêm hàm này trong FileService (dưới)
        if (!$dto) {
            wp_die(__('File not found', 'tmt-crm'), 404);
        }

        // 3) Header + stream
        nocache_headers();

        $filename_original = (string)$dto->originalName;
        $filename_ascii    = sanitize_file_name(remove_accents($filename_original)) ?: 'download';
        $filename_ascii    = str_replace(["\r", "\n"], '', $filename_ascii);
        $filename_utf8     = str_replace(["\r", "\n"], '', $filename_original ?: $filename_ascii);

        header('Content-Type: ' . $dto->mime);
        header('Content-Length: ' . (string)$dto->sizeBytes);
        header(
            'Content-Disposition: attachment; ' .
                'filename="' . wp_basename($filename_ascii) . '"; ' .
                "filename*=UTF-8''" . rawurlencode($filename_utf8)
        );

        if (is_resource($stream)) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        } else {
            echo (string)$stream;
        }
        exit;
    }
}
