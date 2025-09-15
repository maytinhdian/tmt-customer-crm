<?php

declare(strict_types=1);

namespace TMT\CRM\Infrastructure\Setup\Migration;

abstract class BaseMigrator implements SchemaMigratorInterface
{
    protected \wpdb $db;
    protected string $charset_collate;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->charset_collate = $this->db->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php'; // dbDelta
    }

    protected function set_version(string $version): void
    {
        update_option($this->version_option_name(), $version, true);
    }

    protected function get_version(): string
    {
        return (string) get_option($this->version_option_name(), '');
    }

    private function version_option_name(): string
    {
        return 'tmt_crm_schema_' . static::module_key() . '_ver';
    }
}
