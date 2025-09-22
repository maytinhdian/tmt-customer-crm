<?php


namespace TMT\CRM\Core\Notifications\Infrastructure\Seeder;


final class NotificationsSeeder
{
    public static function seed(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tmt_crm_notification_templates';


        // Mảng template mặc định: key => [name, channel, subject, body, placeholders]
        $defaults = [
            'CompanyCreated:notice' => [
                'name' => 'Notice: CompanyCreated',
                'channel' => 'notice',
                'subject' => null,
                'body' => 'User {{actor_id}} đã tạo công ty {{company_name}} (ID: {{company_id}}) lúc {{occurred_at}}.',
                'placeholders' => json_encode(['actor_id', 'occurred_at', 'company_name', 'company_id'], JSON_UNESCAPED_UNICODE),
            ],
            'CompanyCreated:email' => [
                'name' => 'Email: CompanyCreated',
                'channel' => 'email',
                'subject' => '[CRM] Công ty mới: {{company_name}}',
                'body' => 'Công ty {{company_name}} (ID: {{company_id}}) vừa được tạo bởi user {{actor_id}} lúc {{occurred_at}}.',
                'placeholders' => json_encode(['actor_id', 'occurred_at', 'company_name', 'company_id'], JSON_UNESCAPED_UNICODE),
            ],
            'CompanySoftDeleted:notice' => [
                'name' => 'Notice: CompanySoftDeleted',
                'channel' => 'notice',
                'subject' => null,
                'body' => 'User {{actor_id}} đã xoá mềm công ty {{company_name}} (ID: {{company_id}}) lúc {{occurred_at}}.',
                'placeholders' => json_encode(['actor_id', 'occurred_at', 'company_name', 'company_id'], JSON_UNESCAPED_UNICODE),
            ],
        ];


        foreach ($defaults as $key => $tpl) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM `{$table}` WHERE `key`=%s", $key));
            if ($exists) continue;
            $wpdb->insert($table, [
                'key' => $key,
                'name' => $tpl['name'],
                'channel' => $tpl['channel'],
                'subject' => $tpl['subject'],
                'body' => $tpl['body'],
                'placeholders' => $tpl['placeholders'],
                'is_active' => 1,
                'version' => '1.0',
                'created_at' => current_time('mysql'),
            ], ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s']);
        }


        // Preference mặc định: bật notice cho mọi người với CompanyCreated
        $pref_table = $wpdb->prefix . 'tmt_crm_notification_preferences';
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM `{$pref_table}` WHERE scope='global' AND event_key=%s AND channel=%s", 'CompanyCreated', 'notice'));
        if (!$exists) {
            $wpdb->insert($pref_table, [
                'scope' => 'global',
                'scope_ref' => '',
                'event_key' => 'CompanyCreated',
                'channel' => 'notice',
                'enabled' => 1,
                'created_at' => current_time('mysql'),
            ], ['%s', '%s', '%s', '%s', '%d', '%s']);
        }


        // Role admin bật email cho CompanyCreated
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM `{$pref_table}` WHERE scope='role' AND scope_ref=%s AND event_key=%s AND channel=%s", 'administrator', 'CompanyCreated', 'email'));
        if (!$exists) {
            $wpdb->insert($pref_table, [
                'scope' => 'role',
                'scope_ref' => 'administrator',
                'event_key' => 'CompanyCreated',
                'channel' => 'email',
                'enabled' => 1,
                'created_at' => current_time('mysql'),
            ], ['%s', '%s', '%s', '%s', '%d', '%s']);
        }
    }
}
