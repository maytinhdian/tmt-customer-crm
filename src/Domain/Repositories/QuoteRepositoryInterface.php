<?php

declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Application\DTO\QuoteDTO;

interface QuoteRepositoryInterface
{
    public function save(QuoteDTO $dto): int;                // insert, trả id
    public function find_by_id(int $id): ?QuoteDTO;
    public function update_status(int $id, string $status): void;
    public function replace_items(int $quote_id, array $items): void; // QuoteItemDTO[]
}
