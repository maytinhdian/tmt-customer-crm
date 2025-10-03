<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Files\Application\Services\Storage;

final class StoredFile
{
    public function __construct(
        public string $storage,           // 'wp_uploads' | 's3' | ...
        public string $path,              // đường dẫn nội bộ (relative hoặc absolute tùy storage)
        public ?string $public_url = null,// URL public nếu có
        public ?string $checksum = null   // ví dụ sha256
    ) {}
}

interface StorageInterface
{
    /**
     * Lưu file tạm (tmp_path) vào storage chính và trả về thông tin đã lưu.
     * @param string $tmp_path      Đường dẫn file tạm (uploaded)
     * @param string $original_name Tên gốc để bắt extension
     * @param string $mime          MIME type
     */
    public function store(string $tmp_path, string $original_name, string $mime): StoredFile;

    /**
     * Xóa vật lý file trong storage (nếu cần).
     */
    public function delete(string $path): bool;
}
