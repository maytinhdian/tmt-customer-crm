<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Password\Application\Services;

use TMT\CRM\Core\Capabilities\Domain\Capability;

final class PolicyService
{
    public function ensure_can(string $action): void
    {
        // Map “action” nội bộ sang capability
        $map = [
            'password.read'    => Capability::PASSWORD_READ,
            'password.create'  => Capability::PASSWORD_CREATE,
            'password.update'  => Capability::PASSWORD_UPDATE,
            'password.delete'  => Capability::PASSWORD_DELETE,                                                                                                   
            'password.restore' => Capability::PASSWORD_RESTORE,
            'password.reveal'  => Capability::PASSWORD_REVEAL
        ];

        $cap = $map[$action] ?? '';
        if (!$cap || !current_user_can($cap)) {
            wp_die(__('Bạn không có quyền thực hiện hành động này.', 'tmt-crm'));
        }
    }
}
