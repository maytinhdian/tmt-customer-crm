<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Accounts\Application\Services;

use TMT\CRM\Domain\Repositories\UserRepositoryInterface;
use TMT\CRM\Core\Accounts\Domain\DTO\UserDTO;

final class UserService
{
    public function __construct(private UserRepositoryInterface $repo) {}

    /**
     * Tìm user cho Select2.
     * @return array{items: array<array{id:int,label:string}>, more: bool}
     */
    public function search_for_select2(string $q, int $page = 1, int $per_page = 20, string $must_cap = ''): array
    {
        return $this->repo->search_for_select($q, $page, $per_page, $must_cap);
    }

    /** @return array<int,UserDTO> */
    public function find_by_ids(array $ids): array
    {
        return $this->repo->find_by_ids($ids);
    }

    public function label_by_id(int $id): ?string
    {
        return $this->repo->find_label_by_id($id);
    }
}
