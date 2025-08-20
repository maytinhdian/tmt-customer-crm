<?php

namespace TMT\CRM\Presentation\REST;

use TMT\CRM\Application\Services\InvoiceService;
use TMT\CRM\Application\Services\PaymentService;
use TMT\CRM\Application\DTO\InvoiceDTO;
use TMT\CRM\Application\DTO\PaymentDTO;
use TMT\CRM\Shared\Container;

final class InvoiceController
{
    private static function service(): InvoiceService
    {
        return Container::get('invoice-service');
    }
    private static function payments(): PaymentService
    {
        return Container::get('payment-service');
    }


    public function __construct(private InvoiceService $service) {}

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
        $id = self::service()->create(new InvoiceDTO(
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
        $ok = self::payments()->add(new PaymentDTO(
            $id,
            (float)($d['amount'] ?? 0),
            $d['note'] ?? null
        ));
        return rest_ensure_response(['ok' => $ok]);
    }
}
