<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Infrastructure\Channels;

use TMT\CRM\Core\Notifications\Application\Contracts\ChannelAdapterInterface;
use TMT\CRM\Core\Notifications\Domain\DTO\DeliveryDTO;

final class NoticeChannelAdapter implements ChannelAdapterInterface
{
    /**
     * @param array<string,mixed> $rendered
     */
    public function send(DeliveryDTO $delivery, array $rendered): bool
    {
        add_action('admin_notices', function () use ($rendered) {
            $msg = isset($rendered['body']) ? (string)$rendered['body'] : '';
            echo '<div class="notice notice-success"><p>' . esc_html($msg) . '</p></div>';
        });

        error_log('[Notif] NoticeAdapter sent: ' . json_encode([
            'recipient' => $delivery->recipient_id ?? null,
            'subject'   => $rendered['subject'] ?? '',
        ]));

        return true;
    }
}
