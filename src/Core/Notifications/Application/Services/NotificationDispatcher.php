<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Application\Services;

use TMT\CRM\Core\Notifications\Domain\DTO\{NotificationDTO, DeliveryDTO, TemplateDTO, EventContextDTO};
use TMT\CRM\Domain\Repositories\{NotificationRepositoryInterface, DeliveryRepositoryInterface, TemplateRepositoryInterface};
use TMT\CRM\Core\Notifications\Domain\EventKeys;

final class NotificationDispatcher
{
    public function __construct(
        private NotificationRepositoryInterface $notifications,
        private DeliveryRepositoryInterface $deliveries,
        private TemplateRepositoryInterface $templates,
        private PreferenceService $preferences,
        private TemplateRenderer $renderer,
        private DeliveryService $sender
    ) {}

    /** Điểm vào khi có event domain */
    public function on_event(string $event_key, EventContextDTO $ctx): void
    {
        // TODO: map event -> template -> recipients -> deliveries
        // 1) Chọn template theo event + channel
        // 2) Resolve recipients theo role/user
        // 3) Lưu notification + deliveries
        // 4) send() ngay (MVP) hoặc đẩy queue ở P2
    }
}
