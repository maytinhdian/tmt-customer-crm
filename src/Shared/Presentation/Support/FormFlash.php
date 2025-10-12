<?php
declare(strict_types=1);

namespace TMT\CRM\Shared\Presentation\Support;

final class FormFlash
{
    private static function key(string $screen_id): string
    {
        $user_id = get_current_user_id() ?: 0;
        return 'tmt_crm_form_flash_'.$screen_id.'_'.$user_id;
    }

    /** @param array{old?:array, errors?:array, message?:string} $payload */
    public static function put(string $screen_id, array $payload, int $ttl = 120): void
    {
        set_transient(self::key($screen_id), $payload, $ttl);
    }

    /** @return array{old:array, errors:array, message:string} */
    public static function pull(string $screen_id): array
    {
        $key = self::key($screen_id);
        $data = get_transient($key);
        if (!is_array($data)) {
            return ['old' => [], 'errors' => [], 'message' => ''];
        }
        delete_transient($key);
        return [
            'old'     => (array)($data['old'] ?? []),
            'errors'  => (array)($data['errors'] ?? []),
            'message' => (string)($data['message'] ?? ''),
        ];
    }
}
