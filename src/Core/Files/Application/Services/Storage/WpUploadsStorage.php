<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Files\Application\Services\Storage;

final class WpUploadsStorage implements StorageInterface
{
    private const BASE_DIR = 'tmt-crm';

    public function store(string $tmp_path, string $original_name, string $mime): StoredFile
    {
        $uploads = wp_get_upload_dir();
        $basedir = rtrim($uploads['basedir'], '/');
        $baseurl = rtrim($uploads['baseurl'], '/');

        // Tạo thư mục theo năm/tháng để dễ quản lý
        $subdir = self::BASE_DIR . '/' . gmdate('Y') . '/' . gmdate('m');
        $target_dir = $basedir . '/' . $subdir;

        if (!wp_mkdir_p($target_dir)) {
            throw new \RuntimeException('Không tạo được thư mục lưu trữ.');
        }

        // Tên file an toàn
        $ext = pathinfo($original_name, PATHINFO_EXTENSION);
        $name = pathinfo($original_name, PATHINFO_FILENAME);
        $safe = sanitize_file_name($name);
        $filename = $safe . '-' . wp_generate_password(8, false, false);
        if ($ext) {
            $filename .= '.' . strtolower($ext);
        }

        $target_path = $target_dir . '/' . $filename;
        if (!@copy($tmp_path, $target_path)) {
            throw new \RuntimeException('Lưu file thất bại.');
        }

        // Tính checksum (tùy chọn)
        $checksum = null;
        if (function_exists('hash_file')) {
            $checksum = @hash_file('sha256', $target_path) ?: null;
        }

        $relative_path = $subdir . '/' . $filename;
        $public_url = $baseurl . '/' . $relative_path;

        return new StoredFile(
            storage: 'wp_uploads',
            path: $relative_path,
            public_url: $public_url,
            checksum: $checksum
        );
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
