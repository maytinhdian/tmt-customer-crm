<?php
// ============================================================================
// File: src/Core/Notifications/Infrastructure/Repositories/DbDeliveryRepository.php
// ============================================================================


declare(strict_types=1);


namespace TMT\CRM\Core\Notifications\Infrastructure\Repositories;


use TMT\CRM\Domain\Repositories\DeliveryRepositoryInterface;
use TMT\CRM\Core\Notifications\Domain\DTO\DeliveryDTO;

final class DbDeliveryRepository implements DeliveryRepositoryInterface
{
    public function __construct(private \wpdb $db) {}


    private function table(): string
    {
        return $this->db->prefix . 'tmt_crm_notification_deliveries';
    }


    public function create(DeliveryDTO $dto): int
    {
        $this->db->insert(
            $this->table(),
            [
                'notification_id' => $dto->notification_id,
                'channel' => $dto->channel,
                'recipient_type' => $dto->recipient_type,
                'recipient_value' => $dto->recipient_value,
                'status' => $dto->status,
                'attempts' => $dto->attempts,
                'last_error' => $dto->last_error,
                'sent_at' => $dto->sent_at,
                'read_at' => $dto->read_at,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s']
        );
        return (int) $this->db->insert_id;
    }
    /** @return DeliveryDTO[] */
    public function find_unread_for_user(int $user_id, int $limit = 20): array
    {
        // recipient_type='user' + recipient_value = user id (string)
        $limit = max(1, min(200, $limit));
        $sql = "SELECT * FROM `{$this->table()}` WHERE status = 'unread' AND recipient_type = 'user' AND recipient_value = %s ORDER BY id DESC LIMIT {$limit}";
        $rows = $this->db->get_results($this->db->prepare($sql, (string)$user_id), ARRAY_A) ?: [];
        return array_map([$this, 'map_row'], $rows);
    }


    public function mark_read(int $delivery_id, int $user_id): bool
    {
        // đảm bảo đúng người nhận (đơn giản theo user)
        $sql = "UPDATE `{$this->table()}` SET status='read', read_at = %s WHERE id=%d AND recipient_type='user' AND recipient_value=%s";
        $res = $this->db->query($this->db->prepare($sql, current_time('mysql'), $delivery_id, (string)$user_id));
        return (bool) $res;
    }


    public function update_status(int $delivery_id, string $status, ?string $error = null): bool
    {
        $this->db->update(
            $this->table(),
            [
                'status' => $status,
                'last_error' => $error,
                'sent_at' => ($status === 'sent') ? current_time('mysql') : null,
            ],
            ['id' => $delivery_id],
            ['%s', '%s', '%s'],
            ['%d']
        );
        return (bool) $this->db->rows_affected;
    }


    private function map_row(array $row): DeliveryDTO
    {
        $d = new DeliveryDTO();
        $d->id = (int) $row['id'];
        $d->notification_id = (int) $row['notification_id'];
        $d->channel = (string) $row['channel'];
        $d->recipient_type = (string) $row['recipient_type'];
        $d->recipient_value = (string) $row['recipient_value'];
        $d->status = (string) $row['status'];
        $d->attempts = (int) $row['attempts'];
        $d->last_error = $row['last_error'] !== null ? (string)$row['last_error'] : null;
        $d->sent_at = $row['sent_at'] !== null ? (string)$row['sent_at'] : null;
        $d->read_at = $row['read_at'] !== null ? (string)$row['read_at'] : null;
        return $d;
    }
}
