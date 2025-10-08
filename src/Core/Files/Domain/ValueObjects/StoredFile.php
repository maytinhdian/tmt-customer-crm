<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Files\Domain\ValueObjects;

final class StoredFile
{
    public function __construct(
        private string $storage,        // driver name, e.g. 'wp_uploads'
        private string $path,           // relative path inside storage
        private ?string $publicUrl = null,
        private ?string $checksum = null,
        private ?int $sizeBytes = null,
        private ?string $mime = null,
    ) {}

    public function storage(): string
    {
        return $this->storage;
    }
    public function path(): string
    {
        return $this->path;
    }
    public function publicUrl(): ?string
    {
        return $this->publicUrl;
    }
    public function checksum(): ?string
    {
        return $this->checksum;
    }
    public function sizeBytes(): ?int
    {
        return $this->sizeBytes;
    }
    public function mime(): ?string
    {
        return $this->mime;
    }
}
