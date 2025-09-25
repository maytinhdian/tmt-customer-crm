<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Application\Services;

use TMT\CRM\Core\Notifications\Domain\Repositories\NotificationLogRepositoryInterface;

final class LogWriterService
{
    public function __construct(private NotificationLogRepositoryInterface $logs) {}

    /**
     * Ghi log chung (thấp nhất). Trả về ID bản ghi.
     *
     * @param array $data [
     *   'template_code','event_name','channel','recipient','subject',
     *   'status' => 'success'|'fail',
     *   'error'  => string|null,
     *   'run_id','idempotency_key',
     *   'meta'   => array,
     *   'created_at' => Y-m-d H:i:s (optional)
     * ]
     */
    public function write(array $data): int
    {
        // Sanitize tối thiểu, tránh null key thiếu
        $payload = [
            'template_code'   => (string)($data['template_code'] ?? ''),
            'event_name'      => (string)($data['event_name'] ?? ''),
            'channel'         => (string)($data['channel'] ?? ''),
            'recipient'       => (string)($data['recipient'] ?? ''),
            'subject'         => isset($data['subject']) ? (string)$data['subject'] : null,
            'status'          => ($data['status'] ?? 'success') === 'fail' ? 'fail' : 'success',
            'error'           => isset($data['error']) ? (string)$data['error'] : null,
            'run_id'          => isset($data['run_id']) ? (string)$data['run_id'] : null,
            'idempotency_key' => (string)($data['idempotency_key'] ?? ''),
            'meta'            => is_array($data['meta'] ?? null) ? $data['meta'] : [],
            'created_at'      => (string)($data['created_at'] ?? current_time('mysql')),
        ];

        return $this->logs->create($payload);
    }

    /** Helper ghi thành công. */
    public function write_success(
        string $template_code,
        string $event_name,
        string $channel,
        string $recipient,
        string $subject,
        string $run_id,
        string $idempotency_key,
        array $meta = []
    ): int {
        return $this->write([
            'template_code'   => $template_code,
            'event_name'      => $event_name,
            'channel'         => $channel,
            'recipient'       => $recipient,
            'subject'         => $subject,
            'status'          => 'success',
            'error'           => null,
            'run_id'          => $run_id,
            'idempotency_key' => $idempotency_key,
            'meta'            => $meta,
        ]);
    }

    /** Helper ghi lỗi chung. */
    public function write_fail(
        string $template_code,
        string $event_name,
        string $channel,
        string $recipient,
        string $subject,
        string $run_id,
        string $idempotency_key,
        string $error_code,
        array $meta = []
    ): int {
        return $this->write([
            'template_code'   => $template_code,
            'event_name'      => $event_name,
            'channel'         => $channel,
            'recipient'       => $recipient,
            'subject'         => $subject,
            'status'          => 'fail',
            'error'           => $error_code,
            'run_id'          => $run_id,
            'idempotency_key' => $idempotency_key,
            'meta'            => $meta,
        ]);
    }

    /** Helper: bị throttle. */
    public function write_throttled(
        string $template_code,
        string $event_name,
        string $channel,
        string $recipient,
        string $subject,
        string $run_id,
        string $idempotency_key,
        array $meta = []
    ): int {
        $meta['reason'] = $meta['reason'] ?? 'throttle';
        return $this->write_fail(
            $template_code,
            $event_name,
            $channel,
            $recipient,
            $subject,
            $run_id,
            $idempotency_key,
            'throttled',
            $meta
        );
    }

    /** Helper: bị chặn do idempotency (trùng). */
    public function write_idempotent_skip(
        string $template_code,
        string $event_name,
        string $channel,
        string $recipient,
        string $subject,
        string $run_id,
        string $idempotency_key,
        array $meta = []
    ): int {
        $meta['reason'] = $meta['reason'] ?? 'idempotency';
        return $this->write_fail(
            $template_code,
            $event_name,
            $channel,
            $recipient,
            $subject,
            $run_id,
            $idempotency_key,
            'idempotent_skip',
            $meta
        );
    }

    /** Helper: bị chặn do user preference/quiet hours. */
    public function write_preference_block(
        string $template_code,
        string $event_name,
        string $channel,
        string $recipient,
        string $subject,
        string $run_id,
        string $idempotency_key,
        array $meta = []
    ): int {
        $meta['reason'] = $meta['reason'] ?? 'preference';
        return $this->write_fail(
            $template_code,
            $event_name,
            $channel,
            $recipient,
            $subject,
            $run_id,
            $idempotency_key,
            'preference_block',
            $meta
        );
    }
}
