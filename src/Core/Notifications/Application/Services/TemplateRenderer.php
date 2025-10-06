<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Application\Services;

use TMT\CRM\Core\Notifications\Domain\DTO\TemplateDTO;

final class TemplateRenderer
{
    /**
     * @param array<string,mixed> $context
     * @return array{subject:string, body:string}
     */
    public function render(TemplateDTO $tpl, array $context): array
    {
        // Chuẩn hoá: object -> array đệ quy
        $context = $this->toArray($context);

        $subject = (string) $tpl->subject;
        $body    = (string) $tpl->body;

        $flat = $this->flatten($context);
        // (tùy chọn) debug keys:
        // error_log('[Renderer] keys: ' . implode(',', array_keys($flat)));

        foreach ($flat as $k => $v) {
            $needle  = '{{' . $k . '}}';
            $replace = is_scalar($v) ? (string)$v : json_encode($v, JSON_UNESCAPED_UNICODE);
            $subject = str_replace($needle, $replace, $subject);
            $body    = str_replace($needle, $replace, $body);
        }

        return ['subject' => $subject, 'body' => $body];
    }

    /** @param mixed $data @return mixed */
    private function toArray($data)
    {
        if (is_object($data)) $data = get_object_vars($data);
        if (!is_array($data)) return $data;
        foreach ($data as $k => $v) {
            $data[$k] = $this->toArray($v);
        }
        return $data;
    }

    /** @param array<string,mixed> $data */
    private function flatten(array $data, string $prefix = ''): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            $key = $prefix === '' ? (string)$k : $prefix . '.' . (string)$k;
            if (is_array($v)) {
                $out += $this->flatten($v, $key);
            } else {
                $out[$key] = $v;
            }
        }
        return $out;
    }
}
