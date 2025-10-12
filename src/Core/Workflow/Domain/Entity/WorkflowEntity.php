<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Workflow\Domain\Entity;

use TMT\CRM\Core\Workflow\Domain\DTO\WorkflowDTO;

/** Đại diện 1 workflow cấu hình trong hệ thống */
final class WorkflowEntity
{
    public function __construct(
        private int $id,
        private string $name,
        private string $slug,
        private bool $enabled,
        private string $trigger_key,
        private array $conditions, // mảng Condition
        private array $actions,    // mảng Action
        private array $metadata = []
    ) {}

    public static function from_dto(WorkflowDTO $dto): self
    {
        return new self(
            $dto->id ?? 0,
            $dto->name,
            $dto->slug,
            $dto->enabled,
            $dto->trigger_key,
            $dto->conditions,
            $dto->actions,
            $dto->metadata
        );
    }

    public function to_dto(): WorkflowDTO
    {
        return new WorkflowDTO(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            enabled: $this->enabled,
            trigger_key: $this->trigger_key,
            conditions: $this->conditions,
            actions: $this->actions,
            metadata: $this->metadata
        );
    }

    // getters
    public function id(): int { return $this->id; }
    public function name(): string { return $this->name; }
    public function slug(): string { return $this->slug; }
    public function enabled(): bool { return $this->enabled; }
    public function trigger_key(): string { return $this->trigger_key; }
    public function conditions(): array { return $this->conditions; }
    public function actions(): array { return $this->actions; }
    public function metadata(): array { return $this->metadata; }
}
