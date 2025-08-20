<?php

namespace TMT\CRM\Presentation\REST;

use TMT\CRM\Application\Services\Invoice_Service;
use TMT\CRM\Application\Services\Payment_Service;
use TMT\CRM\Application\DTO\Invoice_DTO;
use TMT\CRM\Application\DTO\Payment_DTO;
use TMT\CRM\Shared\Container;

final class Invoice_Controller
{
    private static function service(): Invoice_Service
    {
        return Container::get('invoice-service');
    }
    private static function payments(): Payment_Service
    {
        return Container::get('payment-service');
    }


    public function __construct(private Invoice_Service $service) {}

    public function create(\WP_REST_Request $req)
    {
        $data = $req->get_json_params();
        $id = $this->service->create((int)$data['customer_id'], (float)$data['total']);
        return rest_ensure_response(['id' => $id]);
    }

    public function pay(\WP_REST_Request $req)
    {
        $id = (int)$req['id'];
        $amount = (float)$req['amount'];
        $ok = $this->service->add_payment($id, $amount);
        return rest_ensure_response(['ok' => $ok]);
    }
    //TODO: Error
    public static function store(\WP_REST_Request $req)
    {
        $d = $req->get_json_params() ?: [];
        $id = self::service()->create(new Invoice_DTO(
            (int)($d['customer_id'] ?? 0),
            (float)($d['total'] ?? 0),
            $d['quotation_id'] ?? null,
            $d['status'] ?? \TMT\CRM\Shared\Status::INVOICE_UNPAID,
            (float)($d['paid'] ?? 0)
        ));
        return rest_ensure_response(['id' => $id]);
    }

    // POST /invoices/{id}/payments (shorthand)
    public static function add_payment(\WP_REST_Request $req)
    {
        $id = (int)$req['id'];
        $d = $req->get_json_params() ?: [];
        $ok = self::payments()->add(new Payment_DTO(
            $id,
            (float)($d['amount'] ?? 0),
            $d['note'] ?? null
        ));
        return rest_ensure_response(['ok' => $ok]);
    }
}
