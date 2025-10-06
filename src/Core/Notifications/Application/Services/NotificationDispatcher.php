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

    // /** @param array|object $payload */
    // public function handle($payload): void
    // {
    //     $event_key = is_array($payload) ? (string)($payload['event_key'] ?? '')
    //         : (string)($payload->event_key ?? '');
    //     $context   = is_array($payload) ? (array)($payload['context'] ?? [])
    //         : (array)($payload->context ?? []);
    //     if ($event_key === '') {
    //         return;
    //     }

    //     // Channels (P0: notice + email optional)
    //     $channels = ['admin_notice']; // default
    //     if (method_exists($this->preferences, 'channels_for')) {
    //         $c = $this->preferences->channels_for($event_key);
    //         if (is_array($c) && $c) {
    //             $channels = $c;
    //         }
    //     }

    //     // Simple default template per event
    //     // $tpl = new TemplateDTO(
    //     //     subject: $event_key . ' occurred',
    //     //     body: 'Event {{event_key}} for {{company.name}} by {{user.display_name}}'
    //     // );
    //     $tpl = new TemplateDTO(
    //         subject: 'Event {{event_key}} thành công',
    //         body: 'Công ty: {{company.name}} — Người tạo: {{user.display_name}}'
    //     );
    //     // $rendered = $this->renderer->render($tpl, ['event_key'=>$event_key] + $context);
    //     $rendered = $this->renderer->render($tpl, ['event_key' => $event_key]);

    //     foreach ($channels as $ch) {
    //         if (!$this->preferences->allow($event_key, $ch)) {
    //             continue;
    //         }
    //         $delivery = new DeliveryDTO(
    //             channel: (string)$ch,
    //             recipients: [],
    //             meta: []
    //         );
    //         try {
    //             $this->delivery->send($delivery, $rendered);
    //         } catch (\Throwable $e) {
    //             error_log('[NotificationDispatcher] send error: ' . $e->getMessage());
    //         }
    //     }
    // }
    // ...
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
            subject: 'Event {{event_key}} thành công',
            body: 'Công ty: {{company.name}} — Người tạo: {{user.display_name}}'
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
