<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Password\Application\Services;

use TMT\CRM\Domain\Repositories\PasswordRepositoryInterface;
use TMT\CRM\Modules\Password\Application\DTO\PasswordItemDTO;
use TMT\CRM\Modules\Password\Domain\Entities\PasswordItem;

final class PasswordService
{
    public function __construct(
        private PasswordRepositoryInterface $repo,
        private CryptoService $crypto,
        private PolicyService $policy
    ) {}

    public function list(array $filters, int $page, int $per_page): array
    {
        $this->policy->ensure_can('password.read');
        return $this->repo->list($filters, $page, $per_page);
    }

    public function create(PasswordItemDTO $dto): int
    {
        $this->policy->ensure_can('password.create');

        $enc = $this->crypto->encrypt($dto->password ?? '');
        $entity = new PasswordItem(
            id: null,
            title: $dto->title,
            username: $dto->username,
            ciphertext: $enc['ciphertext'],
            nonce: $enc['nonce'],
            url: $dto->url,
            notes: $dto->notes,
            owner_id: $dto->owner_id,
            company_id: $dto->company_id,
            customer_id: $dto->customer_id,
            subject: $dto->subject,
            category: $dto->category,
            created_at: current_time('mysql'),
            updated_at: current_time('mysql'),
            deleted_at: null
        );
        return $this->repo->insert($entity);
    }

    public function update(int $id, PasswordItemDTO $dto): bool
    {
        $this->policy->ensure_can('password.update');

        $origin = $this->repo->find($id);
        if (!$origin) return false;

        $ciphertext = $origin->ciphertext;
        $nonce = $origin->nonce;

        if ($dto->password !== null && $dto->password !== '') {
            $enc = $this->crypto->encrypt($dto->password);
            $ciphertext = $enc['ciphertext'];
            $nonce = $enc['nonce'];
        }

        $entity = new PasswordItem(
            id: $id,
            title: $dto->title ?: $origin->title,
            username: $dto->username ?? $origin->username,
            ciphertext: $ciphertext,
            nonce: $nonce,
            url: $dto->url ?? $origin->url,
            notes: $dto->notes ?? $origin->notes,
            owner_id: $origin->owner_id,
            company_id: $dto->company_id ?? $origin->company_id,
            customer_id: $dto->customer_id ?? $origin->customer_id,
            subject: $dto->subject,
            category: $dto->category,
            created_at: $origin->created_at,
            updated_at: current_time('mysql'),
            deleted_at: $origin->deleted_at
        );

        return $this->repo->update($entity);
    }

    public function reveal_password(int $id): ?string
    {
        $this->policy->ensure_can('password.reveal'); // hành động nhạy cảm

        $entity = $this->repo->find($id);
        if (!$entity || $entity->deleted_at) return null;

        return $this->crypto->decrypt($entity->ciphertext, $entity->nonce);
    }

    public function soft_delete(int $id): bool
    {
        $this->policy->ensure_can('password.delete');
        return $this->repo->soft_delete($id, get_current_user_id(), null);
    }

    public function restore(int $id): bool
    {
        $this->policy->ensure_can('password.restore');
        return $this->repo->restore($id);
    }
}
