<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Application\Services;

use TMT\CRM\Core\Notifications\Domain\DTO\TemplateDTO;

/** Renderer tối giản: thay {{key}} trong subject/body */
final class TemplateRenderer
{
    /**
     * @param array<string,mixed> $context
     * @return array{subject:string, body:string}
     */
    public function render(TemplateDTO $tpl, array $context): array
    {
        $subject = (string)($tpl->subject ?? '');
        $body    = (string)($tpl->body ?? '');

        foreach ($context as $k => $v) {
            $needle = '{{' . $k . '}}';
            $subject = str_replace($needle, (string)$v, $subject);
            $body    = str_replace($needle, (string)$v, $body);
        }

        return ['subject' => $subject, 'body' => $body];
    }
}
