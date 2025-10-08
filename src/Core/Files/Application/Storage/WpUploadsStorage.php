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
        $uploads  = wp_get_upload_dir();
        $fullPath = $uploads['basedir'] . $path;

        if (!is_readable($fullPath)) {
            return new \WP_Error('not_found', 'File not found', ['status' => 404]);
        }

        $fh = @fopen($fullPath, 'rb');
        if ($fh === false) {
            return new \WP_Error('open_failed', 'Cannot open file', ['status' => 500]);
        }
        return $fh;
    }
    
    public function delete(string $path): bool
    {
        $uploads = wp_get_upload_dir();
        $basedir = rtrim($uploads['basedir'], '/');
        $file = $basedir . '/' . ltrim($path, '/');
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true;
    }
}
