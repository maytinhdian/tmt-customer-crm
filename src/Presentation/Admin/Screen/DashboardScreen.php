<?php
namespace TMT\CRM\Presentation\Admin\Screen;

defined('ABSPATH') || exit;

final class DashboardScreen
{
    public static function on_load(): void
    {
        // enqueue scripts/styles nếu cần
        // add_action('admin_enqueue_scripts', ... );
    }

    public static function render(): void
    {
        echo '<div class="wrap"><h1>TMT CRM – Dashboard</h1><p>Dashboard đã nạp OK.</p></div>';
    }
}
