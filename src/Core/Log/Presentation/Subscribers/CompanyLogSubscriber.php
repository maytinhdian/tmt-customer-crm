<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Log\Presentation\Subscribers;

use TMT\CRM\Core\Events\Domain\Contracts\EventInterface;
use TMT\CRM\Core\Events\Domain\Contracts\EventSubscriberInterface;
use TMT\CRM\Shared\Logging\LoggerInterface;
use TMT\CRM\Shared\Logging\LogLevel;

/**
 * CompanyLogSubscriber
 * - Ghi nhận các sự kiện Company.* vào log (file + DB qua Logger).
 * - Tự chọn level dựa theo event_name.
 */
final class CompanyLogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /** Khai báo các event cần lắng nghe + priority (10 mặc định) */
    public static function subscribed_events(): array
    {
        return [
            'CompanyCreated'      => 10,
            'CompanyUpdated'      => 10,
            'CompanySoftDeleted'  => 10,
            'CompanyRestored'     => 10,
        ];
    }

    public function handle(EventInterface $event): void
    {
        $event_name = $event->name();
        $payloadObj = $event->payload();
        $metadata   = $event->metadata();

        // Chuyển payload/metadata sang array "an toàn" để log
        $payload = is_array($payloadObj) ? $payloadObj : (array)$payloadObj;

        // Cố gắng rút vài trường phổ biến từ metadata (tuỳ implement)
        $meta = [];
        if (is_object($metadata)) {
            // Thử gọi các getter nếu có
            $meta['event_id']       = method_exists($metadata, 'event_id')       ? $metadata->event_id()       : null;
            $meta['occurred_at']    = (method_exists($metadata, 'occurred_at') && $metadata->occurred_at() instanceof \DateTimeInterface)
                ? $metadata->occurred_at()->format(DATE_ATOM) : null;
            $meta['actor_id']       = method_exists($metadata, 'actor_id')       ? $metadata->actor_id()       : null;
            $meta['correlation_id'] = method_exists($metadata, 'correlation_id') ? $metadata->correlation_id() : null;
            $meta['tenant']         = method_exists($metadata, 'tenant')         ? $metadata->tenant()         : null;
        }

        // Chọn level theo event
        $level = self::chooseLevel($event_name);

        // Ghi log (Channel & target do provider quyết định)
        $this->logger->log($level, sprintf('Event handled: %s', $event_name), [
            'event'       => $event_name,
            'payload'     => $payload,
            'metadata'    => $meta ?: $metadata, // fallback nếu không extract được
            'module'      => 'company',
            'entity'      => 'company',
            // Có thể truyền thêm request_id để trace nếu có
            'request_id'  => $_REQUEST['tmt_request_id'] ?? null,
        ]);
    }

    private static function chooseLevel(string $event_name): string
    {
        $n = strtolower($event_name);
        if (str_contains($n, 'deleted')) {
            return LogLevel::WARNING;
        }
        if (
            str_contains($n, 'failed')
            || str_contains($n, 'error')
        ) {
            return LogLevel::ERROR;
        }
        return LogLevel::INFO;
    }
}
