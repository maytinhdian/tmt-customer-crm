<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Core\Events\Domain\Contracts\EventInterface;

interface EventStoreRepositoryInterface
{
    public function append(EventInterface $event): void;

    /** @return iterable<EventInterface> */
    public function fetch_by_correlation(string $correlation_id): iterable;
}
