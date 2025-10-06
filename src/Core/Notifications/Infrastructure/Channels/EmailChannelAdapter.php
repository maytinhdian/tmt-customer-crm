<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Infrastructure\Channels;

use TMT\CRM\Core\Notifications\Application\Contracts\ChannelAdapterInterface;
use TMT\CRM\Core\Notifications\Domain\DTO\DeliveryDTO;

/**
 * Thin wp_mail() wrapper (disabled if wp_mail not available).
 */
final class EmailChannelAdapter implements ChannelAdapterInterface
{
    public function send(DeliveryDTO $delivery, array $rendered): bool
    {
        if (!function_exists('wp_mail')) {
            return false;
        }
        $to = get_option('admin_email');
        $subject = (string)($rendered['subject'] ?? 'Notification');
        $body    = (string)($rendered['body'] ?? '');
        return (bool)wp_mail($to, $subject, $body);
    }
}
