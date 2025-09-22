<?php

declare(strict_types=1);


namespace TMT\CRM\Core\Notifications\Presentation\Admin\Controller;


use TMT\CRM\Shared\Presentation\Support\View; // bắt buộc dùng View::render_admin_module()
use TMT\CRM\Domain\Repositories\DeliveryRepositoryInterface;
use TMT\CRM\Shared\Container\Container;


final class NotificationController
{
    /** Render Trung tâm thông báo */
    public static function render_index(): void
    {
        $user_id = get_current_user_id();
        $filters = [
            'status' => isset($_GET['status']) ? sanitize_text_field((string) $_GET['status']) : '',
            'channel' => isset($_GET['channel']) ? sanitize_text_field((string) $_GET['channel']) : '',
            'event' => isset($_GET['event']) ? sanitize_text_field((string) $_GET['event']) : '',
        ];


        /** @var DeliveryRepositoryInterface $repo */
        $repo = Container::get(DeliveryRepositoryInterface::class);
        // P0: demo với unread; về sau thay bằng query có filter đầy đủ
        $items = $repo->find_unread_for_user($user_id, 50);


        View::render_admin_module('notifications', 'index', [
            'filters' => $filters,
            'items' => $items,
        ]);
    }


    /** AJAX: đánh dấu đã đọc 1 delivery */
    public static function ajax_mark_read(): void
    {
        if (!isset($_POST['delivery_id'])) {
            wp_send_json_error(['message' => 'Missing delivery_id']);
        }
        $delivery_id = (int) $_POST['delivery_id'];
        $user_id = get_current_user_id();


        /** @var DeliveryRepositoryInterface $repo */
        $repo = Container::get(DeliveryRepositoryInterface::class);
        $ok = $repo->mark_read($delivery_id, $user_id);
        $ok ? wp_send_json_success(['delivery_id' => $delivery_id])
            : wp_send_json_error(['message' => 'Cannot mark as read']);
    }
}
