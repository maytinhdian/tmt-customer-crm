<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Events\Infrastructure\Persistence;

use TMT\CRM\Domain\Repositories\EventStoreRepositoryInterface;
use TMT\CRM\Core\Events\Domain\Contracts\EventInterface;

final class WpdbEventStoreRepository implements EventStoreRepositoryInterface
{
    public function __construct(private \wpdb $db) {}

    public function append(EventInterface $event): void
    {
        $table = $this->db->prefix . 'tmt_crm_event_store';
        $data  = [
            'event_id'    => $event->metadata()->event_id,
            'name'        => $event->name(),
            'occurred_at' => $event->metadata()->occurred_at->format('Y-m-d H:i:s'),
            'payload'     => wp_json_encode($event->payload(), JSON_UNESCAPED_UNICODE),
            'metadata'    => wp_json_encode($event->metadata(), JSON_UNESCAPED_UNICODE),
        ];
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $this->db->insert($table, $data);
    }

    public function fetch_by_correlation(string $correlation_id): iterable
    {
        $table = $this->db->prefix . 'tmt_crm_event_store';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$table} WHERE JSON_EXTRACT(metadata, '$.correlation_id') = %s",
                $correlation_id
            )
        );
        return $rows ?? [];
    }
}
