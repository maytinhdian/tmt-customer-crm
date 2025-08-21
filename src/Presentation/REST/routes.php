<?php

namespace TMT\CRM\Presentation\REST;

final class Routes
{
    public static function register(): void
    {
        register_rest_route('tmt-crm/v1', '/customers', [
            'methods'  => 'GET',
            'callback' => [CustomerController::class, 'index'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);
        register_rest_route('tmt-crm/v1', '/customers', [
            'methods'  => 'POST',
            'callback' => [CustomerController::class, 'store'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);
        // Invoices
        register_rest_route('tmt-crm/v1', '/invoices', [
            'methods' => 'POST',
            'callback' => [InvoiceController::class, 'store'],
            'permission_callback' => fn() => current_user_can('manage_options')
        ]);
        register_rest_route('tmt-crm/v1', '/invoices/(?P<id>\\d+)/payments', [
            'methods' => 'POST',
            'callback' => [InvoiceController::class, 'add_payment'],
            'permission_callback' => fn() => current_user_can('manage_options')
        ]);

        // Payments (liệt kê theo invoice)
        register_rest_route('tmt-crm/v1', '/payments/by-invoice/(?P<id>\\d+)', [
            'methods' => 'GET',
            'callback' => [PaymentController::class, 'index_by_invoice'],
            'permission_callback' => fn() => current_user_can('manage_options')
        ]);
    }
}
