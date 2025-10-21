<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Files\Application\Storage;

use TMT\CRM\Core\Files\Domain\Contracts\StorageInterface;
use TMT\CRM\Core\Files\Domain\ValueObjects\StoredFile;

final class WpUploadsStorage implements StorageInterface
{
    private const BASE_DIR = 'tmt-crm';

    public function store(string $tmp_path, string $original_name, string $mime): StoredFile
    {
        $uploads = wp_get_upload_dir();
        $basedir = rtrim($uploads['basedir'], '/');
        $baseurl = rtrim($uploads['baseurl'], '/');

        // Thư mục theo tháng/năm
        $ym = date('Y/m');
        $target_dir = $basedir . '/' . self::BASE_DIR . '/' . $ym;
        if (!is_dir($target_dir)) {
            wp_mkdir_p($target_dir);
        }

        // Tên file duy nhất
        $ext = pathinfo($original_name, PATHINFO_EXTENSION);
        $slug = pathinfo($original_name, PATHINFO_FILENAME);
        $slug = sanitize_file_name($slug);
        $unique = wp_unique_filename($target_dir, $slug . ($ext ? ".{$ext}" : ''));

        $target = $target_dir . '/' . $unique;
        if (!@move_uploaded_file($tmp_path, $target)) {
            if (!@rename($tmp_path, $target)) {
                throw new \RuntimeException('Không thể di chuyển file upload.');
            }
        }

        $checksum = function_exists('hash_file') ? hash_file('sha256', $target) : null;

        $relative = self::BASE_DIR . '/' . $ym . '/' . $unique;
        $public = $baseurl . '/' . $relative;

        return new StoredFile(
            storage: 'wp_uploads',
            path: $relative,
            publicUrl: $public,
            checksum: $checksum
        );
    }
    /** @return resource|\WP_Error */
    public function read(string $path)
    {
        // 1) Chuẩn hoá base và relative (Windows/Linux)
        $uploads = wp_get_upload_dir();
        $basedir = rtrim(str_replace('\\', '/', (string)$uploads['basedir']), '/');
        $rel     = ltrim(str_replace('\\', '/', (string)$path), '/');

        // 2) Join an toàn
        $full = $basedir . '/' . $rel;

        // 3) Fallback nếu $rel vô tình đã chứa 'wp-content/uploads'
        if (str_contains($rel, 'wp-content/uploads/')) {
            $full = ABSPATH . ltrim($rel, '/');
        }

        // 4) Nếu chưa tồn tại, thử lại với biến thể có/không dấu '/' đầu
        if (!file_exists($full)) {
            $alt = $basedir . '/' . ltrim($rel, '/');
            if (file_exists($alt)) {
                $full = $alt;
            }
        }

        // 5) Windows fallback cho tên Unicode/đường dẫn dài
        $fh = null;
        if (file_exists($full) && is_readable($full)) {
            $fh = @fopen($full, 'rb');
        } elseif (PHP_OS_FAMILY === 'Windows') {
            $winFull = '\\\\?\\' . str_replace('/', '\\', $full);
            if (file_exists($winFull) && is_readable($winFull)) {
                $fh = @fopen($winFull, 'rb');
                if ($fh === false) {
                    // thử lần nữa nếu stream fail dù exists
                    $fh = @fopen($full, 'rb');
                }
            }
        }

        if (!is_resource($fh)) {
            error_log('[TMT Files] read(): blob_missing full=' . $full . ' rel=' . $rel);
            return new \WP_Error('blob_missing', 'File blob not found on disk', ['status' => 404, 'path' => $rel, 'full' => $full]);
        }

        return $fh;
    }
    public function delete(string $path): bool
    {
        return true;
    }
}
