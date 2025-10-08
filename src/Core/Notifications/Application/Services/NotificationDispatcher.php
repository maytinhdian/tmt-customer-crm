<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Application\Services;

use TMT\CRM\Core\Notifications\Domain\DTO\DeliveryDTO;
use TMT\CRM\Core\Notifications\Domain\DTO\TemplateDTO;

/**
 * Receive payload, check preferences, render and deliver.
 * P0: no DB persistence for brevity.
 */
final class NotificationDispatcher
{
    public function __construct(
        private TemplateRenderer $renderer,
        private DeliveryService $delivery,
        private PreferenceService $preferences
    ) {}

    /** @param array|object $payload */
    public function handle(array|object $payload): void
    {
        $event_key = is_array($payload) ? (string)($payload['event_key'] ?? '')
            : (string)($payload->event_key ?? '');
        $raw_ctx   = is_array($payload) ? ($payload['context'] ?? [])
            : ($payload->context ?? []);
        // ✨ QUAN TRỌNG: convert object lồng nhau -> array
        $context   = $this->toArray($raw_ctx);
        if ($event_key === '') return;

        $channels = $this->preferences->channels_for($event_key) ?: ['admin_notice'];

        $tpl = new \TMT\CRM\Core\Notifications\Domain\DTO\TemplateDTO(
            subject: '[Event] {{event_key}} thành công',
            body: 'Công ty: {{company.name}} — Người tạo: {{meta.actor_id}}'
        );

        // thêm event_key vào context để dùng trong template
        $rendered = $this->renderer->render($tpl, ['event_key' => $event_key] + $context);

        foreach ($channels as $ch) {
            if (!$this->preferences->allow($event_key, $ch)) continue;
            $this->delivery->send(
                new \TMT\CRM\Core\Notifications\Domain\DTO\DeliveryDTO($ch),
                $rendered
            );
        }
    }

    // Helper y như trong TemplateRenderer (có thể tách ra Util nếu thích)
    private function toArray($data)
    {
        if (is_object($data)) $data = get_object_vars($data);
        if (!is_array($data)) return $data;
        foreach ($data as $k => $v) $data[$k] = $this->toArray($v);
        return $data;
    }
}
