<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Application\Services;

use TMT\CRM\Core\Notifications\Domain\DTO\TemplateDTO;

final class TemplateRenderer
{
    /** Thay placeholder đơn giản theo context (MVP) */
    public function render(TemplateDTO $tpl, array $context): array
    {
        $subject = $tpl->subject ?? '';
        $body = $tpl->body;
        foreach ($context as $k => $v) {
            $subject = str_replace('{{' . $k . '}}', (string)$v, $subject);
            $body = str_replace('{{' . $k . '}}', (string)$v, $body);
        }
        return ['subject' => $subject, 'body' => $body];
    }
}
