<?php
namespace TMT\CRM\Infrastructure\Integration;

final class WooCommerceSync {
    public static function sync_after_order($order_id): void {
        if (!$order_id) return;
        // TODO: đọc order, map email/phone -> khách hàng, nếu chưa có thì tạo
    }
}