<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Files\Application\DTO;

final class FileDTO
{
    public function __construct(
        public ?int $id = null,
        public string $entity_type = '',
        public int $entity_id = 0,
        public int $attachment_id = 0,
        public int $uploaded_by = 0,
        public ?string $uploaded_at = null
    ) {}

    /** @return array<string, mixed> */
    public function to_array(): array
    {
        return [
            'id'            => $this->id,
            'entity_type'   => $this->entity_type,
            'entity_id'     => $this->entity_id,
            'attachment_id' => $this->attachment_id,
            'uploaded_by'   => $this->uploaded_by,
            'uploaded_at'   => $this->uploaded_at,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function from_array(array $data): self
    {
        return new self(
            id:            isset($data['id']) ? (int)$data['id'] : null,
            entity_type:   (string)($data['entity_type'] ?? ''),
            entity_id:     (int)($data['entity_id'] ?? 0),
            attachment_id: (int)($data['attachment_id'] ?? 0),
            uploaded_by:   (int)($data['uploaded_by'] ?? 0),
            uploaded_at:   isset($data['uploaded_at']) ? (string)$data['uploaded_at'] : null
        );
    }
}
