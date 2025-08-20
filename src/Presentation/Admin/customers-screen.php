<?php
namespace TMT\CRM\Presentation\Admin;

final class Customers_Screen {
    public static function render(): void {
        include TMT_CRM_PATH . 'templates/admin/customers-list.php';
    }
}