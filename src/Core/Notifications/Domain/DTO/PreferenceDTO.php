<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Domain\DTO;

final class PreferenceDTO
{
    public int $id = 0;
    public string $scope = 'global'; // global|role|user
    public string $scope_ref = '';   // ''|role slug|user id
    public string $event_key = '';
    public string $channel = '';
    public bool $enabled = true;
}
