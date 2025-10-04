<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Files\Application\Services;

use TMT\CRM\Core\Files\Application\DTO\FileDTO;
use TMT\CRM\Core\Files\Application\Services\Storage\{StorageInterface, StoredFile};
use TMT\CRM\Domain\Repositories\FileRepositoryInterface;
use TMT\CRM\Shared\Container\Container;

final class FileService
{
    public function __construct(
        private ?StorageInterface $storage = null,
        private ?FileRepositoryInterface $repo = null
    ) {
        $this->storage = $this->storage ?: Container::get(StorageInterface::class);
        if ($this->repo === null && Container::has(FileRepositoryInterface::class)) {
            $this->repo = Container::get(FileRepositoryInterface::class);
        }
    }

    /**
     * Upload file → tạo attachment → ghi mapping (FileDTO) nếu repo có sẵn.
     * @return array{stored:StoredFile,attachment_id:int,file_dto:?FileDTO}
     */
    public function upload_and_attach(string $tmp_path, string $original_name, string $mime, string $entity_type, int $entity_id, int $uploaded_by): array
    {
        $stored = $this->storage->store($tmp_path, $original_name, $mime);
        $attachment_id = $this->register_attachment($stored, $original_name, $mime);

        $dto = null;
        if ($this->repo) {
            $dto = new FileDTO(
                id: null,
                entity_type: $entity_type,
                entity_id: $entity_id,
                attachment_id: $attachment_id,
                uploaded_by: $uploaded_by,
                uploaded_at: current_time('mysql')
            );
            $saved_id = $this->repo->create($dto);
            $dto->id = $saved_id;
        }

        return [
            'stored'        => $stored,
            'attachment_id' => $attachment_id,
            'file_dto'      => $dto,
        ];
    }

    public function delete_by_id(int $id): bool
    {
        if (!$this->repo) {
            return false;
        }
        $dto = $this->repo->find_by_id($id);
        if (!$dto) {
            return false;
        }
        wp_delete_attachment($dto->attachment_id, true);
        return $this->repo->delete($id);
    }

    /** @return FileDTO[] */
    public function list_by_entity(string $entity_type, int $entity_id): array
    {
        if (!$this->repo) {
            return [];
        }
        return $this->repo->find_by_entity($entity_type, $entity_id);
    }

    private function register_attachment(StoredFile $stored, string $original_name, string $mime): int
    {
        $uploads = wp_get_upload_dir();
        $absolute_path = trailingslashit($uploads['basedir']) . ltrim($stored->path, '/');

        $attachment = [
            'guid'           => $stored->public_url ?: (trailingslashit($uploads['baseurl']) . ltrim($stored->path, '/')),
            'post_mime_type' => $mime,
            'post_title'     => sanitize_file_name(pathinfo($original_name, PATHINFO_FILENAME)),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];
        $attachment_id = (int) wp_insert_attachment($attachment, $absolute_path);

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $meta = wp_generate_attachment_metadata($attachment_id, $absolute_path);
        wp_update_attachment_metadata($attachment_id, $meta);

        return $attachment_id;
    }
}
