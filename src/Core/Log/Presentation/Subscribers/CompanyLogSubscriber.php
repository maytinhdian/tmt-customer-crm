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
 * - Chuẩn hoá payload/metadata, tránh rò rỉ dữ liệu nhạy cảm.
 */
final class CompanyLogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Khai báo các event cần lắng nghe.
     * TUỲ Loader của bạn:
     *  - Nếu hỗ trợ [method, priority]: dùng như dưới
     *  - Nếu chỉ hỗ trợ 'method': thay mảng con bằng 'handle'
     */
    public static function subscribed_events(): array
    {
        return [
            'CompanyCreated'     => ['handle', 10],
            'CompanyUpdated'     => ['handle, 10'],
            'CompanySoftDeleted' => ['handle', 10],
            'CompanyRestored'    => ['handle', 10],
        ];
    }

    public function handle(EventInterface $event): void
    {
        $event_name = $event->name();
        $payloadObj = $event->payload();
        $metadata   = $event->metadata();

        // Chuẩn hoá payload an toàn (đệ quy, mask key nhạy cảm)
        $payload = $this->normalize($payloadObj);
        $payload = $this->maskSensitive($payload, [
            'password', 'secret', 'token', 'license', 'license_key', 'api_key', 'client_secret',
        ]);

        // Extract metadata theo readonly properties thay vì method_exists
        $meta_arr = [
            'event_id'       => property_exists($metadata, 'event_id') ? $metadata->event_id : null,
            'occurred_at'    => (property_exists($metadata, 'occurred_at') && $metadata->occurred_at instanceof \DateTimeInterface)
                ? $metadata->occurred_at->format(DATE_ATOM) : null,
            'actor_id'       => property_exists($metadata, 'actor_id') ? $metadata->actor_id : null,
            'correlation_id' => property_exists($metadata, 'correlation_id') ? $metadata->correlation_id : null,
            'tenant'         => property_exists($metadata, 'tenant') ? $metadata->tenant : null,
        ];

        $level = self::chooseLevel($event_name);

        // Ghi log (tuân PSR-3, context phẳng, không object)
        $this->logger->log($level, 'Event handled', [
            'event'          => $event_name,
            'event_id'       => $meta_arr['event_id'] ?? null,
            'correlation_id' => $meta_arr['correlation_id'] ?? null,
            'actor_id'       => $meta_arr['actor_id'] ?? null,
            'occurred_at'    => $meta_arr['occurred_at'] ?? null,
            'tenant'         => $meta_arr['tenant'] ?? null,
            'module'         => 'company',
            'entity'         => 'company',
            'payload'        => $payload,
            'request_id'     => $_REQUEST['tmt_request_id'] ?? null,
        ]);
    }

    private static function chooseLevel(string $event_name): string
    {
        $n = strtolower($event_name);
        if (str_contains($n, 'deleted')) {
            return LogLevel::WARNING;
        }
        if (str_contains($n, 'failed') || str_contains($n, 'error')) {
            return LogLevel::ERROR;
        }
        return LogLevel::INFO;
    }

    /**
     * Chuẩn hoá object/array scalar hoá (đệ quy).
     */
    private function normalize(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 5) {
            return '[depth_exceeded]';
        }
        if (is_null($value) || is_scalar($value)) {
            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = $this->normalize($v, $depth + 1);
            }
            return $out;
        }
        if (is_object($value)) {
            // Ưu tiên public props; tránh serialize private/protected “độc”
            $vars = get_object_vars($value);
            $out  = [];
            foreach ($vars as $k => $v) {
                $out[$k] = $this->normalize($v, $depth + 1);
            }
            // Nếu object có __toString(): thêm thông tin gợi ý
            if (method_exists($value, '__toString')) {
                $out['__toString'] = (string)$value;
            }
            $out['__class'] = get_class($value);
            return $out;
        }
        return '[unsupported_type]';
    }

    /**
     * Mask các key nhạy cảm trong mảng (đệ quy).
     */
    private function maskSensitive(array $data, array $keysToMask): array
    {
        $mask = static fn($v) => is_string($v) ? (mb_strlen($v) > 8 ? substr($v, 0, 2) . str_repeat('*', mb_strlen($v) - 4) . substr($v, -2) : '***') : '***';

        $out = [];
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $out[$k] = $this->maskSensitive($v, $keysToMask);
                continue;
            }
            $lower = strtolower((string)$k);
            $shouldMask = false;
            foreach ($keysToMask as $needle) {
                if (str_contains($lower, $needle)) {
                    $shouldMask = true;
                    break;
                }
            }
            $out[$k] = $shouldMask ? $mask($v) : $v;
        }
        return $out;
    }
}
