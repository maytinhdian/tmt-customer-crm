<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

interface QuoteQueryRepositoryInterface
{
    /**
     * @param array{
     *   paged?: int,
     *   per_page?: int,
     *   search?: string|null,
     *   status?: string|null,
     *   orderby?: string,   // code|created_at|expires_at|grand_total|status
     *   order?: string      // ASC|DESC
     * } $args
     * @return array{
     *   items: array<int, array<string, mixed>>,
     *   total: int,
     *   status_counts: array<string,int>
     * }
     */
    public function paginate(array $args): array;
}
