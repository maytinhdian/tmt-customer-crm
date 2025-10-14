<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Files\Application\Services;

use TMT\CRM\Core\Files\Domain\DTO\FileDTO;
use TMT\CRM\Core\Files\Domain\Contracts\StorageInterface;
use TMT\CRM\Core\Files\Domain\Repositories\FileRepositoryInterface;
use TMT\CRM\Shared\Logging\LoggerInterface;
use TMT\CRM\Core\Events\Domain\Contracts\EventBusInterface;
use TMT\CRM\Core\Events\Domain\Events\DefaultEvent;
use TMT\CRM\Core\Events\Domain\ValueObjects\EventMetadata;


final class FileService
{
    public function __construct(
        private StorageInterface $storage,
        private FileRepositoryInterface $repo,
        private LoggerInterface $logger,
        private EventBusInterface $events
    ) {}

    /**
     * Chuẩn bị tải xuống: policy + mở stream + trả kèm metadata.
     * @return array{stream:mixed,dto:FileDto}|\WP_Error
     */
    public function prepareDownload(int $fileId, int $currentUserId)
    {
        $file = $this->repo->findById($fileId);
        if (!$file) {
            return new \WP_Error('not_found', 'File not found', ['status' => 404]);
        }
        if (!PolicyService::canRead($currentUserId, $file)) {
            $this->logger->warning('files.download_denied', ['file_id' => $fileId, 'user_id' => $currentUserId]);
            return new \WP_Error('forbidden', 'Not allowed', ['status' => 403]);
        }

        $stream = $this->storage->read($file->path);
        if (is_wp_error($stream)) {
            return $stream;
        }

        return ['stream' => $stream, 'dto' => $file];
    }

    /**
     * @param array{tmp_name:string,name:string,type:string,size:int} $upload
     */
    public function storeFromUpload(array $upload, string $entityType, int $entityId, int $currentUserId, array $meta = []): FileDto
    {
        $stored = $this->storage->store($upload['tmp_name'], $upload['name'], $upload['type']);

        $dto = new FileDTO(
            id: null,
            entityType: $entityType,
            entityId: $entityId,
            storage: $stored->storage(),
            path: $stored->path(),
            originalName: (string)$upload['name'],
            mime: $stored->mime() ?? (string)$upload['type'],
            sizeBytes: $stored->sizeBytes() ?? (int)($upload['size'] ?? 0),
            checksum: $stored->checksum(),
            version: 1,
            visibility: 'private',
            uploadedBy: $currentUserId,
            uploadedAt: current_time('mysql'),
            meta: $meta
        );

        $id = $this->repo->create($dto);
        $dto->id = $id;

        $this->logger->info('files.uploaded', [
            'file_id' => $id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $currentUserId,
            'mime' => $dto->mime,
            'size' => $dto->sizeBytes
        ]);

        $this->events->publish(new DefaultEvent(
            'FileUploaded',
            (object)[
                'file_id'     => $id,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'size'        => $dto->sizeBytes,
                'mime'        => $dto->mime,
            ],
            $this->newMeta(actorId: $currentUserId)
        ));

        return $dto;
    }

    /** @return FileDTO[] */
    public function list(string $entityType, int $entityId, bool $withDeleted = false): array
    {
        return $this->repo->findByEntity($entityType, $entityId, $withDeleted);
    }

    /** Returns stream or WP_Error; controller will handle headers/echo */
    // public function download(int $fileId, int $currentUserId)
    // {
    //     $file = $this->repo->findById($fileId);
    //     if (!$file) {
    //         return new \WP_Error('not_found', 'File not found', ['status' => 404]);
    //     }
    //     if (!PolicyService::canRead($currentUserId, $file)) {
    //         $this->logger->warning('files.download_denied', ['file_id' => $fileId, 'user_id' => $currentUserId]);
    //         return new \WP_Error('forbidden', 'Not allowed', ['status' => 403]);
    //     }
    //     return $this->storage->read($file->path);
    // }
    /** Tiện ích nếu bạn vẫn muốn gọi trực tiếp stream (giữ tương thích) */
    public function download(int $fileId, int $currentUserId)
    {
        $res = $this->prepareDownload($fileId, $currentUserId);
        return is_wp_error($res) ? $res : $res['stream'];
    }
    public function softDelete(int $fileId, int $currentUserId): bool
    {
        $file = $this->repo->findById($fileId);
        if (!$file || !PolicyService::canDelete($currentUserId, $file)) {
            return false;
        }
        $ok = $this->repo->softDelete($fileId);
        if ($ok) {
            $this->logger->info('files.deleted', ['file_id' => $fileId, 'user_id' => $currentUserId]);
            $this->events->publish(new DefaultEvent(
                'FileDeleted',
                (object)[
                    'file_id'     => $fileId,
                    'entity_type' => $file->entityType,
                    'entity_id'   => $file->entityId,
                ],
                $this->newMeta(actorId: $currentUserId)
            ));
        }
        return $ok;
    }

    public function restore(int $fileId, int $currentUserId): bool
    {
        $file = $this->repo->findById($fileId);
        if (!$file || !PolicyService::canRestore($currentUserId, $file)) {
            return false;
        }
        $ok = $this->repo->restore($fileId);
        if ($ok) {
            $this->logger->info('files.restored', ['file_id' => $fileId, 'user_id' => $currentUserId]);
            $this->events->publish(new DefaultEvent(
                'FileRestored',
                (object)['file_id' => $fileId],
                $this->newMeta(actorId: $currentUserId)
            ));
        }
        return $ok;
    }

    public function purge(int $fileId, int $currentUserId): bool
    {
        $file = $this->repo->findById($fileId);
        if (!$file || !current_user_can(PolicyService::CAP_DELETE)) {
            return false;
        }
        // Delete physical file first (best-effort)
        $this->storage->delete($file->path);
        $ok = $this->repo->purge($fileId);
        if ($ok) {
            $this->logger->info('files.purged', ['file_id' => $fileId, 'user_id' => $currentUserId]);

            $this->events->publish(new DefaultEvent(
                'FilePurged',
                (object)['file_id' => $fileId],
                $this->newMeta(actorId: $currentUserId)
            ));
        }

        return $ok;
    }
    public function getById(int $fileId): ?FileDTO
    {
        return $this->repo->findById($fileId);
    }

    public function updateMeta(int $fileId, array $meta): bool
    {
        return $this->repo->updateMeta($fileId, $meta);
    }
    /** Tạo EventMetadata đúng chữ ký dựa trên WP timezone + UUID */
    private function newMeta(?int $actorId = null, ?string $correlationId = null, ?string $tenant = null): EventMetadata
    {
        $tz = function_exists('wp_timezone') ? wp_timezone() : new \DateTimeZone('UTC');
        $now = new \DateTimeImmutable('now', $tz);

        // Ưu tiên WP UUID4, fallback nếu môi trường quá cũ
        $eventId = function_exists('wp_generate_uuid4')
            ? wp_generate_uuid4()
            : (function_exists('random_bytes') ? bin2hex(random_bytes(16)) : uniqid('', true));

        return new EventMetadata(
            event_id: $eventId,
            occurred_at: $now,
            actor_id: $actorId,
            correlation_id: $correlationId,
            tenant: $tenant
        );
    }
}
