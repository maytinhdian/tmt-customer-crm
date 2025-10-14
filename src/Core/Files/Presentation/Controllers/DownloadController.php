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

    public static function handle(): void
    {
        $fileId = isset($_GET['file_id']) ? (int)$_GET['file_id'] : 0;
        $nonce  = isset($_GET['_wpnonce']) ? (string)$_GET['_wpnonce'] : '';

        if (!$fileId || !wp_verify_nonce($nonce, 'tmt_crm_download_file_' . $fileId)) {
            wp_die(__('Invalid request', 'tmt-crm'), 400);
        }

        $currentUserId = get_current_user_id();

        /** @var \TMT\CRM\Core\Files\Application\Services\FileService $svc */
        $svc = Container::get(FileService::class);

        $res = $svc->prepareDownload($fileId, $currentUserId);
        if (is_wp_error($res)) {
            $code = $res->get_error_code();
            $data = (array)$res->get_error_data();
            if ($code === 'blob_missing') {
                $msg = sprintf('File content missing on disk. rel=%s full=%s', (string)($data['path'] ?? 'n/a'), (string)($data['full'] ?? 'n/a'));
                wp_die(esc_html($msg), 404);
            }
            if ($code === 'record_missing') {
                wp_die(__('File metadata not found.', 'tmt-crm'), 404);
            }
            wp_die(esc_html($res->get_error_message()), (int)($data['status'] ?? 400));
        }

        $stream = $res['stream'];
        $dto    = $res['dto'];

        // Headers
        nocache_headers();

        $filename_original = (string)$dto->originalName;
        $filename_ascii    = sanitize_file_name(remove_accents($filename_original)) ?: 'download';
        $filename_ascii    = str_replace(["\r", "\n"], '', $filename_ascii);
        $filename_utf8     = str_replace(["\r", "\n"], '', ($filename_original ?: $filename_ascii));

        // Lấy size thực tế nếu repo chưa có/size=0
        $length = (int)$dto->sizeBytes;
        if ($length <= 0 && is_resource($stream)) {
            $stat = @fstat($stream);
            if (is_array($stat) && isset($stat['size'])) {
                $length = (int)$stat['size'];
            }
        }

        header('Content-Type: ' . $dto->mime);
        if ($length > 0) {
            header('Content-Length: ' . (string)$length);
        }
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

    // public static function handle(): void
    // {
    //     $fileId = isset($_GET['file_id']) ? (int)$_GET['file_id'] : 0;
    //     $nonce  = isset($_GET['_wpnonce']) ? (string)$_GET['_wpnonce'] : '';

    //     if (!$fileId || !wp_verify_nonce($nonce, 'tmt_crm_download_file_' . $fileId)) {
    //         wp_die(__('Invalid request', 'tmt-crm'), 400);
    //     }

    //     $currentUserId = get_current_user_id();

    //     /** @var \TMT\CRM\Core\Files\Application\Services\FileService $svc */
    //     $svc = \TMT\CRM\Shared\Container\Container::get(\TMT\CRM\Core\Files\Application\Services\FileService::class);

    //     // 1) Lấy stream (đã kèm kiểm tra Policy bên trong service)
    //     $stream = $svc->download($fileId, $currentUserId);
    //     if (is_wp_error($stream)) {
    //         wp_die(esc_html($stream->get_error_message()), (int)($stream->get_error_data()['status'] ?? 400));
    //     }

    //     // 2) Lấy metadata đúng file (đừng gọi $svc->list('', 0) nữa)
    //     $dto = $svc->getById($fileId);                 // <-- cần thêm hàm này trong FileService (dưới)
    //     if (!$dto) {
    //         wp_die(__('File not found', 'tmt-crm'), 404);
    //     }

    //     // 3) Header + stream
    //     nocache_headers();

    //     $filename_original = (string)$dto->originalName;
    //     $filename_ascii    = sanitize_file_name(remove_accents($filename_original)) ?: 'download';
    //     $filename_ascii    = str_replace(["\r", "\n"], '', $filename_ascii);
    //     $filename_utf8     = str_replace(["\r", "\n"], '', $filename_original ?: $filename_ascii);

    //     header('Content-Type: ' . $dto->mime);
    //     header('Content-Length: ' . (string)$dto->sizeBytes);
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
}
