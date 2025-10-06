<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Events\Infrastructure\Persistence;

use TMT\CRM\Domain\Repositories\EventStoreRepositoryInterface;
use TMT\CRM\Core\Events\Domain\Contracts\EventInterface;
use TMT\CRM\Core\Events\Domain\Events\DefaultEvent;
use TMT\CRM\Core\Events\Domain\ValueObjects\EventMetadata;

final class WpdbEventStoreRepository implements EventStoreRepositoryInterface
{
    public function __construct(private \wpdb $db) {}

    public function append(EventInterface $event): void
    {
        $table = $this->db->prefix . 'tmt_crm_event_store';
        $meta  = $event->metadata();

        $this->db->insert(
            $table,
            [
                'event_id'       => $meta->event_id,
                'event_name'     => $event->name(),
                'payload_json'   => wp_json_encode($event->payload()),
                'metadata_json'  => wp_json_encode([
                    'actor_id'       => $meta->actor_id,
                    'correlation_id' => $meta->correlation_id,
                    'tenant'         => $meta->tenant,
                ]),
                'occurred_at'    => $meta->occurred_at->format('Y-m-d H:i:s'),
                'actor_id'       => $meta->actor_id,
                'correlation_id' => $meta->correlation_id,
                'tenant'         => $meta->tenant,
                'created_at'     => current_time('mysql', true),
            ],
            ['%s','%s','%s','%s','%s','%d','%s','%s','%s']
        );
    }

    /** @return iterable<EventInterface> */
    public function fetch_by_correlation(string $correlation_id): iterable
    {
        $table = $this->db->prefix . 'tmt_crm_event_store';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$table} WHERE correlation_id = %s ORDER BY id ASC",
                $correlation_id
            ),
            ARRAY_A
        ) ?: [];

        foreach ($rows as $r) {
            $payload = json_decode($r['payload_json'] ?? '{}');
            $metaArr = json_decode($r['metadata_json'] ?? '{}', true) ?: [];
            $meta = new EventMetadata(
                event_id: (string)($r['event_id'] ?? ''),
                occurred_at: new \DateTimeImmutable(($r['occurred_at'] ?? 'now') . ' UTC'),
                actor_id: isset($r['actor_id']) ? (int)$r['actor_id'] : ($metaArr['actor_id'] ?? null),
                correlation_id: (string)($r['correlation_id'] ?? ($metaArr['correlation_id'] ?? '')) ?: null,
                tenant: (string)($r['tenant'] ?? ($metaArr['tenant'] ?? '')) ?: null,
            );
            yield new DefaultEvent((string)$r['event_name'], is_object($payload) ? $payload : (object)$payload, $meta);
        }
    }
}
