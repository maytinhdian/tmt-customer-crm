<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Files\Domain\DTO;

final class FileDTO

{
    public function __construct(
        public ?int $id,
        public string $entityType,
        public int $entityId,
        public string $storage,         // driver name
        public string $path,            // relative path
        public string $originalName,
        public string $mime,
        public int $sizeBytes,
        public ?string $checksum,
        public int $version,
        public string $visibility,      // 'private'|'public'
        public int $uploadedBy,
        public string $uploadedAt,      // Y-m-d H:i:s
        public ?string $updatedAt = null,
        public ?string $deletedAt = null,
        public array $meta = [],
    ) {}
}
