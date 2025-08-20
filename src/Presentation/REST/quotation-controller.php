<?php
namespace TMT\CRM\Presentation\REST;

use TMT\CRM\Application\Services\Quotation_Service;

final class Quotation_Controller {
    public function __construct(private Quotation_Service $service) {}

    public function create(\WP_REST_Request $req) {
        $data = $req->get_json_params();
        $id = $this->service->create((int)$data['customer_id'], (float)$data['total']);
        return rest_ensure_response(['id' => $id]);
    }

    public function accept(\WP_REST_Request $req) {
        $id = (int)$req['id'];
        $ok = $this->service->mark_accepted($id);
        return rest_ensure_response(['ok' => $ok]);
    }
}