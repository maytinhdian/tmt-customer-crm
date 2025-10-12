<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Workflow\Presentation\Admin\Screen;

final class WorkflowListScreen
{
    public static function register_menu(): void
    {
        add_action('admin_menu', function () {
            add_menu_page(
                __('Workflow', 'tmt-crm'),
                __('Workflow', 'tmt-crm'),
                'manage_options',
                'tmt-crm-workflow',
                [self::class, 'render'],
                'dashicons-randomize',
                58
            );
        });
    }

    public static function render(): void
    {
        echo '<div class="wrap"><h1>Workflow (Skeleton)</h1>';
        echo '<p>Đây là màn hình mẫu. Bạn có thể thay bằng WP_List_Table.</p>';
        echo '</div>';
    }
}
