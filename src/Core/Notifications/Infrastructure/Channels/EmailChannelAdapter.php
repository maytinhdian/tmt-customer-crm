<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Infrastructure\Channels;

use TMT\CRM\Core\Notifications\Application\Contracts\ChannelAdapterInterface;
use TMT\CRM\Core\Notifications\Domain\DTO\DeliveryDTO;

final class EmailChannelAdapter implements ChannelAdapterInterface
{
    /**
     * @param array<string,mixed> $rendered
     */
    public function send(DeliveryDTO $delivery, array $rendered): bool
    {
        $subject = (string)($rendered['subject'] ?? '');
        $body    = (string)($rendered['body'] ?? '');

        $to = '';
        if (!empty($delivery->recipient_id)) {
            $u = get_userdata((int)$delivery->recipient_id);
            if ($u && !empty($u->user_email)) {
                $to = (string)$u->user_email;
            }
        }
        if ($to === '') {
            $to = (string)get_option('admin_email');
        }

        $ok = wp_mail($to, $subject, $body, []);
        error_log('[Notif] EmailAdapter sent: ' . json_encode(['to' => $to, 'ok' => $ok]));
        return (bool)$ok;
    }
}
