<?php
// src/Presentation/REST/payment-controller.php
namespace TMT\CRM\Presentation\REST;

use TMT\CRM\Application\Services\Payment_Service;
use TMT\CRM\Application\DTO\Payment_DTO;
use TMT\CRM\Shared\Container;

final class Payment_Controller
{
    private static function service(): Payment_Service
    {
        return Container::get('payment-service');
    }

    public static function store(\WP_REST_Request $req)
    {
        $d = $req->get_json_params() ?: [];
        $ok = self::service()->add(new Payment_DTO(
            (int)($d['invoice_id'] ?? 0),
            (float)($d['amount'] ?? 0),
            $d['note'] ?? null
        ));
        return rest_ensure_response(['ok' => $ok]);
    }

    public static function index_by_invoice(\WP_REST_Request $req)
    {
        $invoice_id = (int)$req['id'];
        $items = self::service()->list_by_invoice($invoice_id);
        return rest_ensure_response(['items' => $items]);
    }
}
