<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Files\Application\Services\Storage;

final class StoredFile
{
    public function __construct(
        public string $storage,
        public string $path,
        public ?string $public_url = null,
        public ?string $checksum = null
    ) {}
}

interface StorageInterface
{
    /**
     * @param string $tmp_path
     * @param string $original_name
     * @param string $mime
     */
    public function store(string $tmp_path, string $original_name, string $mime): StoredFile;

    /**
     * Xóa file vật lý theo đường dẫn tương đối trong storage
     */
    public function delete(string $path): bool;
}
