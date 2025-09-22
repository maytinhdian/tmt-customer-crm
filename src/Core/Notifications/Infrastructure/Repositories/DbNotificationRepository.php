<?php
// ============================================================================
// File: src/Core/Notifications/Infrastructure/Repositories/DbNotificationRepository.php
// ============================================================================


declare(strict_types=1);


namespace TMT\CRM\Core\Notifications\Infrastructure\Repositories;


use TMT\CRM\Domain\Repositories\NotificationRepositoryInterface;
use TMT\CRM\Core\Notifications\Domain\DTO\NotificationDTO;


final class DbNotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(private \wpdb $db) {}


    private function table(): string
    {
        return $this->db->prefix . 'tmt_crm_notifications';
    }


    public function create(NotificationDTO $dto): int
    {
        $this->db->insert(
            $this->table(),
            [
                'event_key' => $dto->event_key,
                'entity_type' => $dto->entity_type,
                'entity_id' => $dto->entity_id,
                'template_key' => $dto->template_key,
                'summary' => $dto->summary,
                'created_at' => $dto->created_at ?: current_time('mysql'),
                'created_by' => $dto->created_by,
            ],
            ['%s', '%s', '%d', '%s', '%s', '%s', '%d']
        );
        return (int) $this->db->insert_id;
    }


    public function find(int $id): ?NotificationDTO
    {
        $sql = "SELECT * FROM `{$this->table()}` WHERE id = %d LIMIT 1";
        $row = $this->db->get_row($this->db->prepare($sql, $id), ARRAY_A);
        if (!$row) return null;
        $dto = new NotificationDTO();
        $dto->id = (int) $row['id'];
        $dto->event_key = (string) $row['event_key'];
        $dto->entity_type = (string) $row['entity_type'];
        $dto->entity_id = (int) $row['entity_id'];
        $dto->template_key = (string) ($row['template_key'] ?? '');
        $dto->summary = (string) ($row['summary'] ?? '');
        $dto->created_at = (string) $row['created_at'];
        $dto->created_by = (int) ($row['created_by'] ?? 0);
        return $dto;
    }
}
