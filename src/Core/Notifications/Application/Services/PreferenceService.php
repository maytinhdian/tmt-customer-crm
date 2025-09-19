<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Application\Services;

use TMT\CRM\Domain\Repositories\PreferenceRepositoryInterface;

final class PreferenceService
{
    public function __construct(private PreferenceRepositoryInterface $prefs) {}

    /** @return array [channel => enabled] */
    public function channels_for_user(int $user_id, string $event_key): array
    {
        return $this->prefs->resolve_for_user($user_id, $event_key);
    }
}
