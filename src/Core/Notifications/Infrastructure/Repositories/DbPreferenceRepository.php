<?php


declare(strict_types=1);


namespace TMT\CRM\Core\Notifications\Infrastructure\Repositories;


use TMT\CRM\Domain\Repositories\PreferenceRepositoryInterface;


final class DbPreferenceRepository implements PreferenceRepositoryInterface
{
    public function __construct(private \wpdb $db) {}


    private function table(): string
    {
        return $this->db->prefix . 'tmt_crm_notification_preferences';
    }


    /**
     * Hợp nhất rule theo thứ tự ưu tiên: user > role > global.
     * Trả về mảng [channel => enabled(bool)] cho event_key.
     */
    public function resolve_for_user(int $user_id, string $event_key): array
    {
        $channels = [];


        // 1) Global
        $sqlG = "SELECT channel, enabled FROM `{$this->table()}` WHERE scope='global' AND event_key=%s";
        foreach ($this->db->get_results($this->db->prepare($sqlG, $event_key), ARRAY_A) ?: [] as $r) {
            $channels[$r['channel']] = (bool) $r['enabled'];
        }


        // 2) Role
        $user = get_userdata($user_id);
        $roles = $user?->roles ?: [];
        if (!empty($roles)) {
            $in = implode(",", array_fill(0, count($roles), '%s'));
            $sqlR = $this->db->prepare(
                "SELECT channel, enabled FROM `{$this->table()}` WHERE scope='role' AND event_key=%s AND scope_ref IN ($in)",
                array_merge([$event_key], $roles)
            );
            foreach ($this->db->get_results($sqlR, ARRAY_A) ?: [] as $r) {
                $channels[$r['channel']] = (bool) $r['enabled'];
            }
        }


        // 3) User
        $sqlU = "SELECT channel, enabled FROM `{$this->table()}` WHERE scope='user' AND event_key=%s AND scope_ref=%s";
        foreach ($this->db->get_results($this->db->prepare($sqlU, $event_key, (string)$user_id), ARRAY_A) ?: [] as $r) {
            $channels[$r['channel']] = (bool) $r['enabled'];
        }


        return $channels;
    }
}
