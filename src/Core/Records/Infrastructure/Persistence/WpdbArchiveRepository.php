<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Records\Infrastructure\Persistence;

use TMT\CRM\Core\Records\Application\DTO\ArchiveDTO;
use TMT\CRM\Core\Records\Domain\Repositories\ArchiveRepositoryInterface;

final class WpdbArchiveRepository implements ArchiveRepositoryInterface
{
    public function store_snapshot(ArchiveDTO $dto): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'crm_archives';

        $data = [
            'entity'           => $dto->entity,
            'entity_id'        => $dto->entity_id,
            'snapshot_json'    => wp_json_encode($dto->snapshot),
            'relations_json'   => $dto->relations ? wp_json_encode($dto->relations) : null,
            'attachments_json' => $dto->attachments ? wp_json_encode($dto->attachments) : null,
            'checksum_sha256'  => $dto->checksum_sha256,
            'purged_by'        => $dto->purged_by,
            'purged_at'        => $dto->purged_at->format('Y-m-d H:i:s'),
            'purge_reason'     => $dto->purge_reason,
        ];
        $formats = ['%s','%d','%s','%s','%s','%s','%d','%s','%s'];

        $wpdb->insert($table, $data, $formats);
        return (int) $wpdb->insert_id;
    }
}
