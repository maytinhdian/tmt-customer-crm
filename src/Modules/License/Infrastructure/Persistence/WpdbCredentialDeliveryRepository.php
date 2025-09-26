<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\License\Infrastructure\Persistence;

use TMT\CRM\Domain\Repositories\CredentialDeliveryRepositoryInterface;

/**
 * Triển khai repository dùng $wpdb (P0: khung).
 * Namespace implement nằm trong module; interface ở TMT\CRM\Domain\Repositories\
 */
final class WpdbCredentialDeliveryRepository implements CredentialDeliveryRepositoryInterface
{
    // public function find_by_id(int $id) { /* ... */ }
}
