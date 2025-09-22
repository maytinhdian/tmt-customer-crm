<?php
// ================================
// File: src/Core/Notifications/Presentation/Admin/Screen/NotificationCenterScreen.php
// ================================


declare(strict_types=1);


namespace TMT\CRM\Core\Notifications\Presentation\Admin\Screen;


use TMT\CRM\Core\Notifications\Presentation\Admin\Controller\NotificationController;
use TMT\CRM\Shared\Infrastructure\Security\Capability;

/**
 * Notification Center Screen
 */
final class NotificationCenterScreen
{
    public const PAGE_SLUG = 'tmt-crm-notifications';


    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'add_menu'], 20);
        // Hook AJAX mark-read
        add_action('wp_ajax_tmt_crm_notifications_mark_read', [NotificationController::class, 'ajax_mark_read']);
    }


    /**
     * NOTE: Đổi 'tools.php' thành slug menu CRM của bạn (vd: 'tmt-crm') khi đã có menu cha riêng.
     */
    public static function add_menu(): void
    {
        add_submenu_page(
            'tmt-crm',
            __('Trung tâm thông báo', 'tmt-crm'),
            __('Thông báo', 'tmt-crm'),
            Capability::CUSTOMER_READ,
            NotificationCenterScreen::PAGE_SLUG,
            [
                NotificationController::class,
                'render_index'
            ],
            20
        );
    }
}
