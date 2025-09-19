<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Domain\DTO;

final class TemplateDTO
{
    public int $id = 0;
    public string $key = '';
    public string $name = '';
    public string $channel = ''; // email|notice|webhook
    public ?string $subject = null; // email
    public string $body = '';
    public array $placeholders = []; // danh sách placeholder hợp lệ
    public bool $is_active = true;
    public string $version = '1.0';
}
