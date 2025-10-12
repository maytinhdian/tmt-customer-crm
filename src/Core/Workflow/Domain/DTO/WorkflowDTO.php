<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Workflow\Domain\DTO;

/** DTO cho Workflow */
final class WorkflowDTO
{
    public function __construct(
        public ?int $id = null,
        public string $name = '',
        public string $slug = '',
        public bool $enabled = true,
        public string $trigger_key = '',
        /** @var array<int, array> */
        public array $conditions = [],
        /** @var array<int, array> */
        public array $actions = [],
        public array $metadata = []
    ) {}

    public static function from_array(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            name: (string)($data['name'] ?? ''),
            slug: (string)($data['slug'] ?? ''),
            enabled: (bool)($data['enabled'] ?? true),
            trigger_key: (string)($data['trigger_key'] ?? ''),
            conditions: (array)($data['conditions'] ?? []),
            actions: (array)($data['actions'] ?? []),
            metadata: (array)($data['metadata'] ?? []),
        );
    }

    public function to_array(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'enabled' => $this->enabled,
            'trigger_key' => $this->trigger_key,
            'conditions' => $this->conditions,
            'actions' => $this->actions,
            'metadata' => $this->metadata,
        ];
    }
}
