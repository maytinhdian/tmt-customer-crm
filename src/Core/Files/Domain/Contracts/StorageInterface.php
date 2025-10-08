<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Files\Domain\Contracts;

use TMT\CRM\Core\Files\Domain\ValueObjects\StoredFile;

interface StorageInterface
{
    /**
     * @param string $tmp_path
     * @param string $original_name
     * @param string $mime
     */
    public function store(string $tmp_path, string $original_name, string $mime): StoredFile;
    /**
     * Đọc nội dung tệp.
     * @param string $path Đường dẫn tương đối trong storage.
     * @return resource|string|\WP_Error Trả về stream handle (resource), hoặc string raw, hoặc \WP_Error nếu lỗi.
     */
    public function read(string $path);
    /**
     * Xóa file vật lý theo đường dẫn tương đối trong storage
     */
    public function delete(string $path): bool;
}
