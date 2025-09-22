<?php
// ============================================================================
// File: src/Core/Notifications/Infrastructure/Repositories/DbTemplateRepository.php
// ============================================================================


declare(strict_types=1);


namespace TMT\CRM\Core\Notifications\Infrastructure\Repositories;


use TMT\CRM\Domain\Repositories\TemplateRepositoryInterface;
use TMT\CRM\Core\Notifications\Domain\DTO\TemplateDTO;


final class DbTemplateRepository implements TemplateRepositoryInterface
{
    public function __construct(private \wpdb $db) {}


    private function table(): string
    {
        return $this->db->prefix . 'tmt_crm_notification_templates';
    }


    public function find_by_key(string $key): ?TemplateDTO
    {
        $sql = "SELECT * FROM `{$this->table()}` WHERE `key`=%s LIMIT 1";
        $row = $this->db->get_row($this->db->prepare($sql, $key), ARRAY_A);
        return $row ? $this->map_row($row) : null;
    }


    /** @return TemplateDTO[] */
    public function find_all_by_channel(string $channel): array
    {
        $sql = "SELECT * FROM `{$this->table()}` WHERE channel=%s AND is_active=1 ORDER BY id DESC";
        $rows = $this->db->get_results($this->db->prepare($sql, $channel), ARRAY_A) ?: [];
        return array_map([$this, 'map_row'], $rows);
    }


    private function map_row(array $row): TemplateDTO
    {
        $t = new TemplateDTO();
        $t->id = (int) $row['id'];
        $t->key = (string) $row['key'];
        $t->name = (string) $row['name'];
        $t->channel = (string) $row['channel'];
        $t->subject = $row['subject'] !== null ? (string)$row['subject'] : null;
        $t->body = (string) $row['body'];
        $t->placeholders = $row['placeholders'] ? (array) json_decode((string)$row['placeholders'], true) : [];
        $t->is_active = (bool) $row['is_active'];
        $t->version = (string) $row['version'];
        return $t;
    }
}
